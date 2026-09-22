<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature\Auth;

use Binaryk\LaravelRestify\Notifications\VerifyEmail;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Schema\Blueprint;
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
    public function a_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        $this->postJson('/auth/register', $this->validPayload(['email' => 'jane@example.com']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    #[Test]
    public function a_valid_registration_creates_the_user(): void
    {
        $this->postJson('/auth/register', $this->validPayload())
            ->assertOk()
            ->assertJsonPath('meta.email_verification_sent', true)
            ->assertJsonPath('meta.token', 'token');

        $this->assertDatabaseHas(User::class, ['email' => 'jane@example.com']);

        $user = User::query()->where('email', 'jane@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * @param  array<string, string>  $overrides
     */
    #[Test]
    #[TestWith([['email' => '']], 'missing email')]
    #[TestWith([['email' => 'not-an-email']], 'invalid email format')]
    #[TestWith([['password' => '', 'password_confirmation' => '']], 'missing password')]
    #[TestWith([['password' => 'abc', 'password_confirmation' => 'abc']], 'password shorter than the minimum')]
    #[TestWith([['password_confirmation' => 'something-else']], 'password confirmation mismatch')]
    public function invalid_input_is_rejected(array $overrides): void
    {
        $this->postJson('/auth/register', $this->validPayload($overrides))
            ->assertUnprocessable();
    }

    #[Test]
    public function the_publishable_register_stub_checks_uniqueness_against_the_configured_table(): void
    {
        $stub = file_get_contents(__DIR__.'/../../../src/Commands/stubs/Auth/RegisterController.stub');

        $this->assertIsString($stub);
        $this->assertStringNotContainsString("Config::get('config.auth.table'", $stub);
        $this->assertStringContainsString("Config::string('restify.auth.table', 'users')", $stub);
        $this->assertStringContainsString("'password' => ['required', 'confirmed', 'min:6']", $stub);
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
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
