<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature\Auth;

use Binaryk\LaravelRestify\Notifications\ForgotPasswordNotification;
use Binaryk\LaravelRestify\Tests\Database\Factories\UserFactory;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class ForgotPasswordUrlHostTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::restifyAuth('auth', ['forgotPassword']);

        config()->set('restify.auth.password_reset_url', 'https://app.restify.test/password/reset?token={token}&email={email}');
        config()->set('app.url', 'https://api.restify.test');
    }

    #[Test]
    public function a_url_targeting_a_foreign_host_is_rejected_and_nothing_is_sent(): void
    {
        Notification::fake();

        $user = UserFactory::one(['email' => 'known@example.com']);

        $this->postJson('auth/forgotPassword', [
            'email' => $user->email,
            'url' => 'https://attacker.test/reset?token={token}&email={email}',
        ])->assertStatus(422)->assertJsonValidationErrors('url');

        Notification::assertNothingSent();
    }

    #[Test]
    #[TestWith(['//attacker.test/reset?token={token}&email={email}'], 'protocol-relative')]
    #[TestWith(['attacker.test/reset?token={token}&email={email}'], 'missing scheme')]
    #[TestWith(['https://app.restify.test@attacker.test/reset?token={token}&email={email}'], 'userinfo trick')]
    #[TestWith(['https://evil.app.restify.test/reset?token={token}&email={email}'], 'subdomain of the allowed host')]
    #[TestWith(['javascript:alert(1)'], 'javascript scheme')]
    public function malicious_url_shapes_are_rejected(string $url): void
    {
        Notification::fake();

        $user = UserFactory::one(['email' => 'known@example.com']);

        $this->postJson('auth/forgotPassword', [
            'email' => $user->email,
            'url' => $url,
        ])->assertStatus(422)->assertJsonValidationErrors('url');

        Notification::assertNothingSent();
    }

    #[Test]
    public function a_url_matching_the_configured_password_reset_host_is_accepted(): void
    {
        Notification::fake();

        $user = UserFactory::one(['email' => 'known@example.com']);

        $this->postJson('auth/forgotPassword', [
            'email' => $user->email,
            'url' => 'HTTPS://APP.RESTIFY.TEST/custom/reset?token={token}&email={email}',
        ])->assertOk();

        Notification::assertSentOnDemand(
            ForgotPasswordNotification::class,
            fn (ForgotPasswordNotification $notification): bool => str_starts_with($notification->url, 'HTTPS://APP.RESTIFY.TEST/custom/reset')
        );
    }

    #[Test]
    public function a_url_matching_the_app_url_host_is_accepted(): void
    {
        Notification::fake();

        $user = UserFactory::one(['email' => 'known@example.com']);

        $this->postJson('auth/forgotPassword', [
            'email' => $user->email,
            'url' => 'https://api.restify.test/reset?token={token}&email={email}',
        ])->assertOk();

        Notification::assertSentOnDemand(ForgotPasswordNotification::class);
    }

    #[Test]
    public function when_url_is_absent_the_configured_template_is_used_unchanged(): void
    {
        Notification::fake();

        $user = UserFactory::one(['email' => 'known@example.com']);

        $this->postJson('auth/forgotPassword', ['email' => $user->email])->assertOk();

        Notification::assertSentOnDemand(
            ForgotPasswordNotification::class,
            fn (ForgotPasswordNotification $notification): bool => str_starts_with($notification->url, 'https://app.restify.test/password/reset')
        );
    }

    #[Test]
    public function when_no_host_is_configured_any_client_url_is_rejected(): void
    {
        config()->set('restify.auth.password_reset_url', '/password/reset?token={token}&email={email}');
        config()->set('app.url', '');

        Notification::fake();

        $user = UserFactory::one(['email' => 'known@example.com']);

        $this->postJson('auth/forgotPassword', [
            'email' => $user->email,
            'url' => 'https://app.restify.test/reset?token={token}&email={email}',
        ])->assertStatus(422)->assertJsonValidationErrors('url');

        Notification::assertNothingSent();
    }
}
