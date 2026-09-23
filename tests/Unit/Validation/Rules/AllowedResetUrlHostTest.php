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
    #[TestWith(['https://good.com/a\b', ['https://good.com'], false], 'a backslash in the path alone (no userinfo) is rejected')]
    #[TestWith(["https://good.com/a\tb", ['https://good.com'], false], 'a raw tab character in the path alone (no userinfo) is rejected')]
    #[TestWith(['https://good.com/?](https://evil.com/?t={token})[&email={email}', ['https://good.com'], false], 'a markdown link injection in the query breaks out of the mailed [url](url) fallback')]
    #[TestWith(['https://good.com/](evil)/reset?token={token}&email={email}', ['https://good.com'], false], 'a markdown link injection in the path')]
    #[TestWith(['https://good.com/<script>alert(1)</script>?token={token}', ['https://good.com'], false], 'angle brackets in the path are rejected')]
    #[TestWith(['https://good.com/reset?token={token}&email={email}&r="onmouseover="x', ['https://good.com'], false], 'a double quote in the query is rejected')]
    #[TestWith(["https://good.com/reset?token={token}&r=`x`", ['https://good.com'], false], 'a backtick in the query is rejected')]
    #[TestWith(["https://good.com/reset?token={token}&r='x'", ['https://good.com'], false], 'a single quote in the query is rejected')]
    #[TestWith(['https://good.com/{tenant}/reset?token={token}&email={email}', ['https://good.com'], false], 'a curly-brace segment that is not the literal {token}/{email} placeholder is rejected')]
    #[TestWith(['https://good.com:443/reset', ['https://good.com'], true], 'an explicit default port for https (443) is normalized away')]
    #[TestWith(['http://good.com:80/reset', ['http://good.com'], true], 'an explicit default port for http (80) is normalized away')]
    #[TestWith(['https://good.com:80/reset', ['https://good.com'], false], 'a non-default port for the scheme is not normalized away')]
    #[TestWith(['https://good.com%5C@evil.com/', ['https://good.com'], false], 'a percent-encoded backslash before @ still forms userinfo and is rejected')]
    #[TestWith(['https://good.com%40evil.com/', ['https://good.com'], false], 'a percent-encoded @ does not split the authority - the literal hostname does not match')]
    #[TestWith(['https://good.com/%40evil.com', ['https://good.com'], true], 'a percent-encoded @ in the path does not affect the host')]
    #[TestWith(['https:///host', ['https://good.com'], false], 'an empty authority (triple slash) fails to parse')]
    #[TestWith(['https://[::1]/reset', ['https://good.com'], false], 'an IPv6 literal host does not match a configured hostname')]
    #[TestWith(['https://[::1]/reset', ['https://[::1]'], true], 'an IPv6 literal host matches when explicitly allowed')]
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
    public function from_config_treats_an_unset_frontend_app_url_as_adding_nothing(): void
    {
        config()->set('restify.auth.password_reset_url', 'https://reset.example.com/password/reset?token={token}&email={email}');
        config()->set('app.url', 'https://api.example.com');
        config()->offsetUnset('restify.auth.frontend_app_url');

        $rule = AllowedResetUrlHost::fromConfig();

        $this->assertTrue($this->passes($rule, 'https://reset.example.com/x'));
        $this->assertTrue($this->passes($rule, 'https://api.example.com/x'));
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
