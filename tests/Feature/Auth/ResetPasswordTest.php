<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature\Auth;

use Binaryk\LaravelRestify\Tests\Database\Factories\UserFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Timebox;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class ResetPasswordTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::restifyAuth('auth', ['resetPassword']);

        config(['restify.auth.password_reset_timebox' => 0]);
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
    public function known_and_unknown_emails_share_the_same_rate_limit_and_get_an_identical_429(): void
    {
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
     * @return array<string, mixed>
     */
    private function headersWithoutDate(TestResponse $response): array
    {
        return Collection::make($response->headers->all())->except('date')->all();
    }
}
