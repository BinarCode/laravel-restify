<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit\Validation\Rules;

use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Binaryk\LaravelRestify\Validation\Rules\AllowedResetUrlHost;
use Illuminate\Translation\PotentiallyTranslatedString;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class AllowedResetUrlHostTest extends IntegrationTestCase
{
    /**
     * @param  list<string>  $allowedOrigins
     */
    #[Test]
    #[TestWith(['https://good.com/reset?token={token}&email={email}', ['https://good.com'], true], 'exact origin, placeholders in the query')]
    #[TestWith(['HTTPS://GOOD.COM/reset', ['https://good.com'], true], 'uppercase host still matches')]
    #[TestWith(['http://good.com/reset', ['https://good.com'], false], 'a scheme downgrade from the configured https is rejected')]
    #[TestWith(['https://good.com:8443/reset', ['https://good.com'], false], 'a port absent from the allowed origin is rejected')]
    #[TestWith(['https://good.com:8443/reset', ['https://good.com:8443'], true], 'a matching port is accepted')]
    #[TestWith(['//evil.com/reset', ['https://good.com'], false], 'protocol-relative url is rejected')]
    #[TestWith(['evil.com/reset', ['https://good.com'], false], 'missing scheme is rejected')]
    #[TestWith(['https://good.com@evil.com/', ['https://good.com'], false], 'userinfo is rejected outright, even naming the allowed host')]
    #[TestWith(['https://evil.good.com/reset', ['https://good.com'], false], 'a subdomain of the allowed origin is rejected')]
    #[TestWith(['javascript:alert(1)', ['https://good.com'], false], 'a non-http(s) scheme is rejected')]
    #[TestWith(['https://good.com/reset', [], false], 'no allowed origin at all rejects every url')]
    #[TestWith(['https://evil.com\@good.com/', ['https://good.com'], false], 'a backslash before @ is rejected (browsers navigate to evil.com)')]
    #[TestWith(['https://evil.com\x@good.com/', ['https://good.com'], false], 'a backslash anywhere in the authority is rejected')]
    #[TestWith(["https://good.com\t@evil.com/", ['https://good.com'], false], 'a raw tab character in the authority is rejected')]
    public function it_validates_the_url_host(string $url, array $allowedOrigins, bool $expectedValid): void
    {
        $rule = new AllowedResetUrlHost($allowedOrigins);

        $this->assertSame($expectedValid, $this->passes($rule, $url));
    }

    #[Test]
    public function from_config_allows_the_password_reset_url_app_url_and_frontend_app_url_hosts(): void
    {
        config()->set('restify.auth.password_reset_url', 'https://reset.example.com/password/reset?token={token}&email={email}');
        config()->set('app.url', 'https://api.example.com');
        config()->set('restify.auth.frontend_app_url', 'https://frontend.example.com');

        $rule = AllowedResetUrlHost::fromConfig();

        $this->assertTrue($this->passes($rule, 'https://reset.example.com/x'));
        $this->assertTrue($this->passes($rule, 'https://api.example.com/x'));
        $this->assertTrue($this->passes($rule, 'https://frontend.example.com/x'));
        $this->assertFalse($this->passes($rule, 'https://attacker.example.com/x'));
    }

    #[Test]
    public function from_config_skips_frontend_app_url_when_the_config_key_is_absent(): void
    {
        config()->set('restify.auth.password_reset_url', 'https://reset.example.com/password/reset?token={token}&email={email}');
        config()->set('app.url', 'https://api.example.com');
        config()->offsetUnset('restify.auth.frontend_app_url');

        $rule = AllowedResetUrlHost::fromConfig();

        $this->assertFalse($this->passes($rule, 'https://frontend.example.com/x'));
    }

    private function passes(AllowedResetUrlHost $rule, string $url): bool
    {
        $failed = false;

        $rule->validate('url', $url, function (string $message) use (&$failed): PotentiallyTranslatedString {
            $failed = true;

            return new PotentiallyTranslatedString($message, app('translator'));
        });

        return ! $failed;
    }
}
