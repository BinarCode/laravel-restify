<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature\Auth;

use Binaryk\LaravelRestify\Http\Controllers\Auth\ResetPasswordController;
use Binaryk\LaravelRestify\Tests\Database\Factories\UserFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\User\SanctumUser;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Timebox;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\PersonalAccessToken;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;

class ResetPasswordTest extends IntegrationTestCase
{
    use RefreshDatabase;

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
    public function a_reset_dispatches_the_password_reset_event_for_the_user(): void
    {
        Event::fake([PasswordReset::class]);

        $user = UserFactory::one(['email' => 'known@example.com']);
        $token = Password::createToken($user);

        $this->postJson('auth/resetPassword', $this->resetPayload($user->email, $token))->assertOk();

        Event::assertDispatchedTimes(PasswordReset::class, 1);
        Event::assertDispatched(PasswordReset::class, fn (PasswordReset $event): bool => $user->is($event->user));
    }

    #[Test]
    public function a_rejected_reset_dispatches_no_password_reset_event(): void
    {
        Event::fake([PasswordReset::class]);

        $user = UserFactory::one(['email' => 'known@example.com']);
        Password::createToken($user);

        $this->postJson('auth/resetPassword', $this->resetPayload($user->email, 'not-the-right-token'))
            ->assertStatus(JsonResponse::HTTP_BAD_REQUEST);

        Event::assertNotDispatched(PasswordReset::class);
    }

    #[Test]
    public function a_reset_rotates_the_remember_token(): void
    {
        $user = UserFactory::one(['email' => 'known@example.com', 'remember_token' => 'stolen-remember-token']);
        $token = Password::createToken($user);

        $this->postJson('auth/resetPassword', $this->resetPayload($user->email, $token))->assertOk();

        $this->assertDatabaseMissing(User::class, ['id' => $user->id, 'remember_token' => 'stolen-remember-token']);

        $rememberToken = User::query()->whereKey($user->id)->value('remember_token');

        $this->assertIsString($rememberToken);
        $this->assertSame(ResetPasswordController::REMEMBER_TOKEN_LENGTH, strlen($rememberToken));
    }

    #[Test]
    #[TestWith([true, 1], 'revocation on')]
    #[TestWith([false, 3], 'revocation off')]
    public function a_reset_revokes_the_users_sanctum_tokens_only_when_configured(bool $revokeTokens, int $remainingTokens): void
    {
        config([
            'restify.auth.user_model' => SanctumUser::class,
            'restify.auth.revoke_tokens_on_reset' => $revokeTokens,
        ]);

        $user = SanctumUser::query()->create(['name' => 'Known', 'email' => 'known@example.com', 'password' => Hash::make('original-password')]);
        $otherUser = SanctumUser::query()->create(['name' => 'Other', 'email' => 'other@example.com', 'password' => Hash::make('original-password')]);

        $user->createToken('laptop');
        $user->createToken('phone');
        $otherUser->createToken('laptop');

        $token = Password::createToken($user);

        $this->postJson('auth/resetPassword', $this->resetPayload($user->email, $token))->assertOk();

        $this->assertDatabaseCount(PersonalAccessToken::class, $remainingTokens);
        $this->assertDatabaseHas(PersonalAccessToken::class, [
            'tokenable_type' => $otherUser->getMorphClass(),
            'tokenable_id' => $otherUser->getKey(),
        ]);
    }

    #[Test]
    public function a_failed_token_revocation_rolls_back_the_password_change(): void
    {
        Event::fake([PasswordReset::class]);
        Exceptions::fake();

        $failingRevocationUser = new class extends SanctumUser
        {
            public function tokens(): MorphMany
            {
                throw new RuntimeException('token store unavailable');
            }
        };

        config(['restify.auth.user_model' => $failingRevocationUser::class]);

        $originalPassword = Hash::make('original-password');
        $user = SanctumUser::query()->create(['name' => 'Known', 'email' => 'known@example.com', 'password' => $originalPassword, 'remember_token' => 'stolen-remember-token']);
        $token = Password::createToken($user);

        $this->postJson('auth/resetPassword', $this->resetPayload($user->email, $token))->assertServerError();

        $this->assertDatabaseHas(SanctumUser::class, [
            'id' => $user->id,
            'password' => $originalPassword,
            'remember_token' => 'stolen-remember-token',
        ]);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
        Event::assertNotDispatched(PasswordReset::class);
    }

    #[Test]
    public function a_published_config_without_the_revoke_key_still_revokes_sanctum_tokens(): void
    {
        /** @var array<string, mixed> $authConfig */
        $authConfig = config('restify.auth');

        config([
            'restify.auth' => Arr::except($authConfig, 'revoke_tokens_on_reset'),
            'restify.auth.user_model' => SanctumUser::class,
        ]);

        $user = SanctumUser::query()->create(['name' => 'Known', 'email' => 'known@example.com', 'password' => Hash::make('original-password')]);
        $user->createToken('laptop');

        $token = Password::createToken($user);

        $this->postJson('auth/resetPassword', $this->resetPayload($user->email, $token))->assertOk();

        $this->assertDatabaseCount(PersonalAccessToken::class, 0);
    }

    #[Test]
    #[TestWith(['Old@Example.com', 'Old@Example.com'], 'a legacy mixed-case row with its exact email')]
    #[TestWith(['new@example.com', 'New@Example.com'], 'a lowercase row with a mixed-case email')]
    #[TestWith(['new@example.com', 'new@example.com'], 'a lowercase row with its exact email')]
    public function the_email_matches_the_stored_row_exactly_or_lowercased(string $storedEmail, string $submittedEmail): void
    {
        $user = UserFactory::one(['email' => $storedEmail, 'password' => Hash::make('original-password')]);
        $token = Password::createToken($user);

        $this->postJson('auth/resetPassword', [
            'email' => $submittedEmail,
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        $currentPassword = DB::table($this->getTable(User::class))->where('id', $user->id)->value('password');

        $this->assertTrue(Hash::check('new-password', $currentPassword));
    }

    #[Test]
    public function an_exact_email_match_wins_over_the_lowercased_row(): void
    {
        UserFactory::one(['email' => 'old@example.com']);
        $user = UserFactory::one(['email' => 'Old@Example.com', 'password' => Hash::make('original-password')]);
        $token = Password::createToken($user);

        $this->postJson('auth/resetPassword', [
            'email' => 'Old@Example.com',
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        $currentPassword = DB::table($this->getTable(User::class))->where('id', $user->id)->value('password');

        $this->assertTrue(Hash::check('new-password', $currentPassword));
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
    public function known_and_unknown_emails_share_the_same_rate_limit_and_get_an_identical_429(): void
    {
        $this->freezeTime();
        config(['cache.default' => 'array']);
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

        $knownThrottled = $this->postJson('auth/resetPassword', $payload('known@example.com'));
        $unknownThrottled = $this->postJson('auth/resetPassword', $payload('unknown@example.com'));

        $knownThrottled->assertStatus(JsonResponse::HTTP_TOO_MANY_REQUESTS);
        $unknownThrottled->assertStatus(JsonResponse::HTTP_TOO_MANY_REQUESTS);

        $this->assertSame($knownThrottled->getContent(), $unknownThrottled->getContent());
        $this->assertSame($this->headersWithoutDate($knownThrottled), $this->headersWithoutDate($unknownThrottled));
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

    /**
     * @return array<string, string>
     */
    private function resetPayload(string $email, string $token): array
    {
        return [
            'email' => $email,
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function headersWithoutDate(TestResponse $response): array
    {
        return Collection::make($response->headers->all())->except('date')->all();
    }
}
