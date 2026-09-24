<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit\Notifications;

use Binaryk\LaravelRestify\Notifications\ForgotPasswordNotification;
use Binaryk\LaravelRestify\Tests\Database\Factories\UserFactory;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Binaryk\LaravelRestify\Validation\Rules\AllowedResetUrlHost;
use Illuminate\Translation\PotentiallyTranslatedString;
use PHPUnit\Framework\Attributes\Test;

class ForgotPasswordNotificationTest extends IntegrationTestCase
{
    #[Test]
    public function a_url_with_markdown_link_syntax_renders_a_second_attacker_controlled_link_in_the_mail(): void
    {
        // MailMessage::action() puts the raw url in two places (see
        // vendor/laravel/framework/.../Notifications/resources/views/email.blade.php):
        // the button's href, and a plain-text fallback line templated as
        // literal Markdown - `[{{ $displayableActionUrl }}]({{ $actionUrl }})` -
        // before the whole message is parsed as Markdown. Blade's `{{ }}`
        // only HTML-escapes ("<", ">", "&", "\"", "'"); it does not escape
        // "[" "]" "(" ")", so an attacker-controlled url containing them can
        // close that markdown link early and open a second, real one.
        $maliciousUrl = 'https://app.restify.test/?](https://attacker.test/?t=leaked-token)[';

        $user = UserFactory::one(['email' => 'known@example.com']);

        $html = (new ForgotPasswordNotification($maliciousUrl))->toMail($user)->render()->toHtml();

        $this->assertStringContainsString('attacker.test', $html);

        // This is exactly why AllowedResetUrlHost must reject a url template
        // shaped like this before it ever reaches the notification - the
        // mail template itself offers no protection once the string is built.
        $rejected = false;

        (new AllowedResetUrlHost(['https://app.restify.test']))->validate(
            'url',
            $maliciousUrl,
            function (string $message) use (&$rejected): PotentiallyTranslatedString {
                $rejected = true;

                return new PotentiallyTranslatedString($message, app('translator'));
            }
        );

        $this->assertTrue($rejected, 'AllowedResetUrlHost must reject this url so it never reaches the notification.');
    }
}
