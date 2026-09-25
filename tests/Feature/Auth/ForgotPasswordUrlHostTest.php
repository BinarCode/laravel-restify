<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature\Auth;

use Binaryk\LaravelRestify\Notifications\ForgotPasswordNotification;
use Binaryk\LaravelRestify\Tests\Database\Factories\UserFactory;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
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
    #[TestWith(['https://attacker.test\@app.restify.test/reset?token={token}&email={email}'], 'backslash-userinfo bypass (browsers navigate to attacker.test)')]
    #[TestWith(['https://attacker.test\x@app.restify.test/reset?token={token}&email={email}'], 'a backslash anywhere in the authority')]
    #[TestWith(["https://app.restify.test\t@attacker.test/reset?token={token}&email={email}"], 'a raw tab character in the authority')]
    #[TestWith(['http://app.restify.test/reset?token={token}&email={email}'], 'a scheme downgrade from the configured https')]
    #[TestWith(['https://app.restify.test:8443/reset?token={token}&email={email}'], 'a port not present in the configured origin')]
    #[TestWith(['https://app.restify.test/?](https://attacker.test/?t={token})[&email={email}'], 'a markdown link injection in the query breaks out of the mailed [url](url) fallback')]
    #[TestWith(['https://app.restify.test/{tenant}/reset?token={token}&email={email}'], 'a curly-brace segment that is not the literal {token}/{email} placeholder')]
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
    public function a_url_longer_than_2048_characters_is_rejected(): void
    {
        Notification::fake();

        $user = UserFactory::one(['email' => 'known@example.com']);

        $url = 'https://app.restify.test/reset?token={token}&email={email}&pad='.str_repeat('a', 2048);

        $this->postJson('auth/forgotPassword', [
            'email' => $user->email,
            'url' => $url,
        ])->assertStatus(422)->assertJsonValidationErrors('url');

        Notification::assertNothingSent();
    }

    #[Test]
    public function an_unknown_email_with_a_foreign_url_gets_the_same_validation_error(): void
    {
        Notification::fake();

        $this->postJson('auth/forgotPassword', [
            'email' => 'unknown@example.com',
            'url' => 'https://attacker.test/reset?token={token}&email={email}',
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
            function (ForgotPasswordNotification $notification) use ($user): bool {
                return str_starts_with($notification->url, 'HTTPS://APP.RESTIFY.TEST/custom/reset')
                    && ! str_contains($notification->url, '{token}')
                    && ! str_contains($notification->url, '{email}')
                    && str_contains($notification->url, 'email=known%40example.com')
                    && Password::tokenExists($user, $this->tokenFromUrl($notification->url));
            }
        );
    }

    #[Test]
    public function a_client_url_padded_with_whitespace_is_trimmed_before_validation_and_accepted(): void
    {
        Notification::fake();

        $user = UserFactory::one(['email' => 'known@example.com']);

        $this->postJson('auth/forgotPassword', [
            'email' => $user->email,
            'url' => ' https://app.restify.test/custom/reset?token={token}&email={email} ',
        ])->assertOk();

        Notification::assertSentOnDemand(
            ForgotPasswordNotification::class,
            fn (ForgotPasswordNotification $notification): bool => $notification->url === 'https://app.restify.test/custom/reset?token='.$this->tokenFromUrl($notification->url).'&email=known%40example.com'
        );
    }

    #[Test]
    public function the_mailed_notification_renders_only_the_accepted_link_with_nothing_injected(): void
    {
        Notification::fake();

        $user = UserFactory::one(['email' => 'known@example.com']);

        $this->postJson('auth/forgotPassword', [
            'email' => $user->email,
            'url' => 'https://app.restify.test/custom/reset?token={token}&email={email}',
        ])->assertOk();

        Notification::assertSentOnDemand(
            ForgotPasswordNotification::class,
            function (ForgotPasswordNotification $notification) use ($user): bool {
                $html = $notification->toMail($user)->render()->toHtml();

                return str_contains($html, 'app.restify.test/custom/reset')
                    && ! str_contains($html, 'attacker.test')
                    && ! str_contains($html, 'evil');
            }
        );
    }

    #[Test]
    public function a_url_matching_the_app_url_host_is_rejected_when_frontend_app_url_is_set_to_a_different_host(): void
    {
        config()->set('restify.auth.frontend_app_url', 'https://app.restify.test');

        Notification::fake();

        $user = UserFactory::one(['email' => 'known@example.com']);

        $this->postJson('auth/forgotPassword', [
            'email' => $user->email,
            'url' => 'https://api.restify.test/reset?token={token}&email={email}',
        ])->assertUnprocessable()->assertJsonValidationErrors('url');

        Notification::assertNothingSent();
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
    public function special_characters_in_the_email_are_percent_encoded_in_the_mailed_url(): void
    {
        Notification::fake();

        $user = UserFactory::one(['email' => 'a+b@example.com']);

        $this->postJson('auth/forgotPassword', ['email' => $user->email])->assertOk();

        Notification::assertSentOnDemand(
            ForgotPasswordNotification::class,
            fn (ForgotPasswordNotification $notification): bool => str_contains($notification->url, 'email=a%2Bb%40example.com')
                && ! str_contains($notification->url, 'email=a+b@example.com')
        );
    }

    #[Test]
    public function a_configured_password_reset_url_carrying_an_unrecognised_placeholder_still_allows_its_host(): void
    {
        config()->set('restify.auth.password_reset_url', 'https://app.restify.test/{locale}/reset?token={token}&email={email}');

        Notification::fake();

        $user = UserFactory::one(['email' => 'known@example.com']);

        $this->postJson('auth/forgotPassword', [
            'email' => $user->email,
            'url' => 'https://app.restify.test/en/reset?token={token}&email={email}',
        ])->assertOk();

        Notification::assertSentOnDemand(ForgotPasswordNotification::class);
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

    private function tokenFromUrl(string $url): string
    {
        /** @var array<string, string> $query */
        $query = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return $query['token'];
    }
}
