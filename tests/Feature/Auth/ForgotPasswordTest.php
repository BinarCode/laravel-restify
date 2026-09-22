<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature\Auth;

use Binaryk\LaravelRestify\Notifications\ForgotPasswordNotification;
use Binaryk\LaravelRestify\Tests\Database\Factories\UserFactory;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class ForgotPasswordTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::restifyAuth('auth', ['forgotPassword']);
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
    #[TestWith([[]], 'email missing')]
    #[TestWith([['email' => 'not-an-email']], 'email malformed')]
    #[TestWith([['email' => 'known@example.com', 'url' => 12345]], 'url not a string')]
    public function invalid_input_is_rejected(array $payload): void
    {
        $this->postJson('auth/forgotPassword', $payload)->assertStatus(422);
    }
}
