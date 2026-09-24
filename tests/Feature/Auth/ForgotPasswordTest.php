<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature\Auth;

use Binaryk\LaravelRestify\Notifications\ForgotPasswordNotification;
use Binaryk\LaravelRestify\Tests\Database\Factories\UserFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Timebox;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;

class ForgotPasswordTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::restifyAuth('auth', ['forgotPassword']);

        config(['restify.auth.password_reset_timebox' => 0]);
    }

    #[Test]
    public function a_known_email_receives_the_reset_notification_with_a_token_and_the_email(): void
    {
        Notification::fake();

        $user = UserFactory::one(['email' => 'known@example.com']);

        $this->postJson('auth/forgotPassword', ['email' => $user->email])
            ->assertOk()
            ->assertExactJson(['message' => 'Reset password link sent to your email.']);

        Notification::assertSentOnDemand(
            ForgotPasswordNotification::class,
            function (ForgotPasswordNotification $notification, array $channels, AnonymousNotifiable $notifiable) use ($user): bool {
                return $notifiable->routes['mail'] === $user->email
                    && str_contains($notification->url, 'email='.rawurlencode($user->email))
                    && ! str_contains($notification->url, '{token}')
                    && ! str_contains($notification->url, '{email}');
            }
        );
    }

    #[Test]
    public function an_unknown_email_receives_the_identical_response_and_nothing_is_sent(): void
    {
        Notification::fake();

        $this->postJson('auth/forgotPassword', ['email' => 'unknown@example.com'])
            ->assertOk()
            ->assertExactJson(['message' => 'Reset password link sent to your email.']);

        Notification::assertNothingSent();
    }

    #[Test]
    public function a_known_and_an_unknown_email_get_a_byte_identical_response(): void
    {
        Notification::fake();

        $user = UserFactory::one(['email' => 'known@example.com']);

        $knownResponse = $this->postJson('auth/forgotPassword', ['email' => $user->email]);
        $unknownResponse = $this->postJson('auth/forgotPassword', ['email' => 'unknown@example.com']);

        $knownResponse->assertOk();
        $unknownResponse->assertOk();
        $this->assertSame($knownResponse->getContent(), $unknownResponse->getContent());
    }

    #[Test]
    public function an_unknown_email_writes_no_password_reset_token(): void
    {
        $this->postJson('auth/forgotPassword', ['email' => 'unknown@example.com'])->assertOk();

        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    #[Test]
    public function the_response_is_computed_inside_a_timebox_for_both_known_and_unknown_emails(): void
    {
        Notification::fake();

        $this->mock(Timebox::class, function (MockInterface $mock): void {
            $mock->shouldReceive('call')
                ->twice()
                ->andReturnUsing(fn (callable $callback) => $callback());
        });

        $user = UserFactory::one(['email' => 'known@example.com']);

        $this->postJson('auth/forgotPassword', ['email' => $user->email])->assertOk();
        $this->postJson('auth/forgotPassword', ['email' => 'unknown@example.com'])->assertOk();
    }

    #[Test]
    public function a_failure_while_notifying_a_known_account_still_returns_the_generic_success_response(): void
    {
        Exceptions::fake();

        $user = UserFactory::one(['email' => 'known@example.com']);

        Password::shouldReceive('createToken')->once()->andThrow(new RuntimeException('reset pipeline failed'));

        $this->postJson('auth/forgotPassword', ['email' => $user->email])
            ->assertOk()
            ->assertExactJson(['message' => 'Reset password link sent to your email.']);

        Exceptions::assertReported(RuntimeException::class);
    }

    #[Test]
    public function known_and_unknown_emails_share_the_same_rate_limit_and_get_an_identical_429(): void
    {
        $this->freezeTime();
        config(['cache.default' => 'array', 'app.debug' => false]);
        $this->withMiddleware(ThrottleRequests::class);

        for ($i = 0; $i < 6; $i++) {
            $this->postJson('auth/forgotPassword', ['email' => 'known@example.com']);
        }

        $knownThrottled = $this->postJson('auth/forgotPassword', ['email' => 'known@example.com']);
        $unknownThrottled = $this->postJson('auth/forgotPassword', ['email' => 'unknown@example.com']);

        $knownThrottled->assertStatus(JsonResponse::HTTP_TOO_MANY_REQUESTS);
        $unknownThrottled->assertStatus(JsonResponse::HTTP_TOO_MANY_REQUESTS);

        $this->assertSame($knownThrottled->getContent(), $unknownThrottled->getContent());
        $this->assertSame($this->headersWithoutDate($knownThrottled), $this->headersWithoutDate($unknownThrottled));
    }

    #[Test]
    public function the_token_mailed_by_forgot_password_actually_resets_the_password(): void
    {
        Route::restifyAuth('auth', ['forgotPassword', 'resetPassword']);
        Notification::fake();

        $user = UserFactory::one(['email' => 'known@example.com', 'password' => $originalPassword = Hash::make('original-password')]);

        $this->postJson('auth/forgotPassword', ['email' => $user->email])->assertOk();

        $token = null;

        Notification::assertSentOnDemand(
            ForgotPasswordNotification::class,
            function (ForgotPasswordNotification $notification) use (&$token): bool {
                /** @var array<string, string> $query */
                $query = [];
                parse_str((string) parse_url($notification->url, PHP_URL_QUERY), $query);
                $token = $query['token'];

                return true;
            }
        );

        $this->assertNotNull($token);

        $this->postJson('auth/resetPassword', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        $this->assertDatabaseMissing(User::class, [
            'id' => $user->id,
            'password' => $originalPassword,
        ]);

        $currentPassword = DB::table($this->getTable(User::class))->where('id', $user->id)->value('password');

        $this->assertTrue(Hash::check('new-password', $currentPassword));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[Test]
    #[TestWith([[]], 'email missing')]
    #[TestWith([['email' => 'not-an-email']], 'email malformed')]
    #[TestWith([['email' => 'known@example.com', 'url' => 12345]], 'url not a string')]
    #[TestWith([['email' => 'known@example.com', 'url' => null]], 'url null')]
    #[TestWith([['email' => 'known@example.com', 'url' => '']], 'url empty string')]
    #[TestWith([['email' => 'known@example.com', 'url' => ['https://app.restify.test/reset']]], 'url an array')]
    public function invalid_input_is_rejected(array $payload): void
    {
        $this->postJson('auth/forgotPassword', $payload)->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @return array<string, mixed>
     */
    private function headersWithoutDate(TestResponse $response): array
    {
        return Collection::make($response->headers->all())->except('date')->all();
    }
}
