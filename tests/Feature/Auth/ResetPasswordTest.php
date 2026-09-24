<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature\Auth;

use Binaryk\LaravelRestify\Tests\Database\Factories\UserFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Timebox;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class ResetPasswordTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // Set before the app boots: the named auth limiters are registered
        // during RestifyApplicationServiceProvider::boot(), which resolves
        // (and pins) the RateLimiter's cache store at that point - a later,
        // runtime config() change would no longer reach it.
        config(['cache.default' => 'array']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Route::restifyAuth('auth', ['resetPassword']);

        config([
            'restify.auth.password_reset_timebox' => 0,
            'app.debug' => false,
        ]);
    }

    #[Test]
    public function a_valid_token_resets_the_password_and_burns_the_token(): void
    {
        $originalPassword = Hash::make('original-password');
        $user = UserFactory::one(['email' => 'known@example.com', 'password' => $originalPassword]);
        $token = Password::createToken($user);

        $this->postJson('auth/resetPassword', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk()->assertExactJson(['message' => 'Your password has been successfully reset.']);

        $this->assertDatabaseMissing(User::class, [
            'id' => $user->id,
            'password' => $originalPassword,
        ]);

        $currentPassword = DB::table($this->getTable(User::class))->where('id', $user->id)->value('password');

        $this->assertTrue(Hash::check('new-password', $currentPassword));

        $this->postJson('auth/resetPassword', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'replayed-password',
            'password_confirmation' => 'replayed-password',
        ])->assertStatus(JsonResponse::HTTP_BAD_REQUEST);
    }

    #[Test]
    public function an_unknown_email_receives_the_identical_response_as_an_invalid_token_and_nothing_changes(): void
    {
        $originalPassword = Hash::make('original-password');
        $user = UserFactory::one(['email' => 'known@example.com', 'password' => $originalPassword]);
        $token = Password::createToken($user);

        $invalidTokenResponse = $this->postJson('auth/resetPassword', [
            'email' => $user->email,
            'token' => 'not-the-right-token',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $unknownEmailResponse = $this->postJson('auth/resetPassword', [
            'email' => 'unknown@example.com',
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $invalidTokenResponse->assertStatus(JsonResponse::HTTP_BAD_REQUEST);
        $unknownEmailResponse->assertStatus(JsonResponse::HTTP_BAD_REQUEST);
        $this->assertSame($invalidTokenResponse->getContent(), $unknownEmailResponse->getContent());

        $this->assertDatabaseHas(User::class, [
            'id' => $user->id,
            'password' => $originalPassword,
        ]);
    }

    #[Test]
    public function an_expired_token_gives_the_same_invalid_token_response(): void
    {
        $user = UserFactory::one(['email' => 'known@example.com']);
        $token = Password::createToken($user);

        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update(['created_at' => now()->subMinutes(61)]);

        $this->postJson('auth/resetPassword', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertStatus(JsonResponse::HTTP_BAD_REQUEST)
            ->assertExactJson(['message' => 'Provided invalid token.']);
    }

    #[Test]
    public function another_users_valid_token_is_rejected_and_that_users_password_is_unchanged(): void
    {
        $userA = UserFactory::one(['email' => 'user-a@example.com']);

        $originalPassword = Hash::make('original-password');
        $userB = UserFactory::one(['email' => 'user-b@example.com', 'password' => $originalPassword]);

        $token = Password::createToken($userA);

        $this->postJson('auth/resetPassword', [
            'email' => $userB->email,
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertStatus(JsonResponse::HTTP_BAD_REQUEST);

        $this->assertDatabaseHas(User::class, [
            'id' => $userB->id,
            'password' => $originalPassword,
        ]);
    }

    #[Test]
    public function the_response_is_computed_inside_a_timebox_for_both_known_and_unknown_emails(): void
    {
        $this->mock(Timebox::class, function (MockInterface $mock): void {
            $mock->shouldReceive('call')
                ->twice()
                ->andReturnUsing(fn (callable $callback) => $callback());
        });

        $user = UserFactory::one(['email' => 'known@example.com']);
        $token = Password::createToken($user);

        $this->postJson('auth/resetPassword', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        $this->postJson('auth/resetPassword', [
            'email' => 'unknown@example.com',
            'token' => 'not-a-token',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertStatus(JsonResponse::HTTP_BAD_REQUEST);
    }

    #[Test]
    public function the_rate_limit_is_keyed_per_email_so_a_throttled_email_does_not_block_a_different_one(): void
    {
        $this->freezeTime();
        $this->withMiddleware(ThrottleRequests::class);

        $payload = fn (string $email): array => [
            'email' => $email,
            'token' => 'wrong-token',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ];

        for ($i = 0; $i < 6; $i++) {
            $this->postJson('auth/resetPassword', $payload('known@example.com'));
        }

        $this->postJson('auth/resetPassword', $payload('known@example.com'))
            ->assertStatus(JsonResponse::HTTP_TOO_MANY_REQUESTS);

        $this->postJson('auth/resetPassword', $payload('unknown@example.com'))
            ->assertStatus(JsonResponse::HTTP_BAD_REQUEST);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[Test]
    #[TestWith([[]], 'all fields missing')]
    #[TestWith([['email' => 'not-an-email', 'token' => 'a-token', 'password' => 'secret', 'password_confirmation' => 'secret']], 'email malformed')]
    #[TestWith([['email' => 'known@example.com', 'token' => 'a-token', 'password' => 'secret', 'password_confirmation' => 'different']], 'password confirmation mismatch')]
    public function invalid_input_is_rejected(array $payload): void
    {
        $this->postJson('auth/resetPassword', $payload)->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }
}
