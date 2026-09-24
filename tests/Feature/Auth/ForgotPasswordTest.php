<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature\Auth;

use Binaryk\LaravelRestify\Notifications\ForgotPasswordNotification;
use Binaryk\LaravelRestify\Tests\Database\Factories\UserFactory;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Timebox;
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
                    && str_contains($notification->url, 'email='.$user->email)
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

    /**
     * @param  array<string, mixed>  $payload
     */
    #[Test]
    #[TestWith([[]], 'email missing')]
    #[TestWith([['email' => 'not-an-email']], 'email malformed')]
    #[TestWith([['email' => 'known@example.com', 'url' => 12345]], 'url not a string')]
    public function invalid_input_is_rejected(array $payload): void
    {
        $this->postJson('auth/forgotPassword', $payload)->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }
}
