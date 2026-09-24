<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature\Auth;

use Binaryk\LaravelRestify\Notifications\VerifyEmail;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class RegisterTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::restifyAuth('auth', ['register']);

        Notification::fake();
    }

    protected function tearDown(): void
    {
        User::$lastCreatedTokenExpiresAt = null;
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function a_custom_auth_table_is_checked_for_uniqueness(): void
    {
        Schema::create('accounts', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('email')->unique();
            $table->timestamps();
        });

        DB::table('accounts')->insert([
            'email' => 'taken@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        config(['restify.auth.table' => 'accounts']);

        $this->postJson('/auth/register', $this->validPayload(['email' => 'taken@example.com']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseMissing(User::class, ['email' => 'taken@example.com']);
    }

    #[Test]
    #[TestWith(['sqlite.users'], 'a connection-qualified table')]
    #[TestWith([User::class], 'a model class configured as the auth table')]
    public function a_connection_or_model_qualified_auth_table_is_checked_for_uniqueness(string $table): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        config(['restify.auth.table' => $table]);

        $this->postJson('/auth/register', $this->validPayload(['email' => 'jane@example.com']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount(User::class, 1);
    }

    #[Test]
    public function an_unset_auth_table_falls_back_to_users(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        config(['restify.auth' => Arr::except(config('restify.auth'), ['table'])]);

        $this->postJson('/auth/register', $this->validPayload(['email' => 'jane@example.com']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount(User::class, 1);
    }

    #[Test]
    public function an_explicit_null_auth_table_falls_back_to_users(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        config(['restify.auth.table' => null]);

        $this->postJson('/auth/register', $this->validPayload(['email' => 'jane@example.com']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount(User::class, 1);
    }

    #[Test]
    public function a_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        $this->postJson('/auth/register', $this->validPayload(['email' => 'jane@example.com']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    #[Test]
    #[TestWith(['secret1'], 'a string password')]
    #[TestWith([123456], 'a numeric password stays scalar and keeps registering')]
    public function a_valid_registration_creates_the_user(string|int $password): void
    {
        $this->postJson('/auth/register', $this->validPayload([
            'password' => $password,
            'password_confirmation' => $password,
        ]))
            ->assertOk()
            ->assertJsonPath('meta.email_verification_sent', true)
            ->assertJsonPath('meta.token', 'token');

        $this->assertDatabaseHas(User::class, ['email' => 'jane@example.com']);

        $user = User::query()->where('email', 'jane@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[Test]
    #[TestWith([['email' => ''], 'email'], 'missing email')]
    #[TestWith([['email' => 'not-an-email'], 'email'], 'invalid email format')]
    #[TestWith([['password' => '', 'password_confirmation' => ''], 'password'], 'missing password')]
    #[TestWith([['password' => 'abc', 'password_confirmation' => 'abc'], 'password'], 'password shorter than the minimum')]
    #[TestWith([['password_confirmation' => 'something-else'], 'password'], 'password confirmation mismatch')]
    #[TestWith([['password' => [1, 2, 3, 4, 5, 6], 'password_confirmation' => [1, 2, 3, 4, 5, 6]], 'password'], 'array password')]
    public function invalid_input_is_rejected(array $overrides, string $expectedField): void
    {
        $this->postJson('/auth/register', $this->validPayload($overrides))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($expectedField);

        $this->assertDatabaseCount(User::class, 0);
    }

    #[Test]
    #[TestWith(['0.5', 30], 'a sub-minute ttl is rounded to seconds instead of truncated to zero')]
    #[TestWith(['60', 3600], 'a whole-minute string ttl converts to seconds')]
    #[TestWith([60, 3600], 'a whole-minute int ttl converts to seconds')]
    #[TestWith([null, null], 'no ttl means no expiry')]
    #[TestWith([0, null], 'a zero ttl never expires')]
    #[TestWith([-1, null], 'a negative ttl never expires')]
    #[TestWith(['abc', null], 'a non-numeric ttl never expires')]
    #[TestWith(['', null], 'an empty ttl never expires')]
    public function the_registration_token_ttl_is_computed_in_seconds(int|string|null $tokenTtl, ?int $expectedExpiresIn): void
    {
        config(['restify.auth.token_ttl' => $tokenTtl]);

        Carbon::setTestNow(now());

        $this->postJson('/auth/register', $this->validPayload())
            ->assertOk()
            ->assertJsonPath('meta.expires_in', $expectedExpiresIn);

        if ($expectedExpiresIn === null) {
            $this->assertNull(User::$lastCreatedTokenExpiresAt);

            return;
        }

        $this->assertNotNull(User::$lastCreatedTokenExpiresAt);
        $this->assertSame($expectedExpiresIn, (int) now()->diffInSeconds(User::$lastCreatedTokenExpiresAt));
    }

    #[Test]
    public function the_publishable_register_stub_checks_uniqueness_against_the_configured_table(): void
    {
        $stub = file_get_contents(__DIR__.'/../../../src/Commands/stubs/Auth/RegisterController.stub');

        $this->assertIsString($stub);
        $this->assertStringNotContainsString("Config::get('config.auth.table'", $stub);
        $this->assertStringContainsString("Config::get('restify.auth.table') ?: 'users'", $stub);
        $this->assertStringContainsString("'password' => ['required', 'confirmed', 'min:6', self::scalarPasswordRule()]", $stub);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret1',
            'password_confirmation' => 'secret1',
        ], $overrides);
    }
}
