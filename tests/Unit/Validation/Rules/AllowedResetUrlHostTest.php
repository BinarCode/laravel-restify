<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit\Validation\Rules;

use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Binaryk\LaravelRestify\Validation\Rules\AllowedResetUrlHost;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class AllowedResetUrlHostTest extends IntegrationTestCase
{
    #[Test]
    #[TestWith(['https://good.com/reset?token={token}&email={email}', ['good.com'], true], 'exact host, placeholders in the query')]
    #[TestWith(['HTTPS://GOOD.COM/reset', ['good.com'], true], 'uppercase host still matches')]
    #[TestWith(['//evil.com/reset', ['good.com'], false], 'protocol-relative url is rejected')]
    #[TestWith(['evil.com/reset', ['good.com'], false], 'missing scheme is rejected')]
    #[TestWith(['https://good.com@evil.com/', ['good.com'], false], 'userinfo trick resolves to the real host')]
    #[TestWith(['https://evil.good.com/reset', ['good.com'], false], 'a subdomain of the allowed host is rejected')]
    #[TestWith(['javascript:alert(1)', ['good.com'], false], 'a non-http(s) scheme is rejected')]
    #[TestWith(['https://good.com/reset', [], false], 'no allowed host at all rejects every url')]
    public function it_validates_the_url_host(string $url, array $allowedHosts, bool $expectedValid): void
    {
        $rule = new AllowedResetUrlHost($allowedHosts);

        $failed = false;

        $rule->validate('url', $url, function () use (&$failed): void {
            $failed = true;
        });

        $this->assertSame($expectedValid, ! $failed);
    }

    #[Test]
    #[TestWith(['https://good.com/reset', 'good.com'])]
    #[TestWith(['HTTPS://GOOD.COM/reset', 'good.com'])]
    #[TestWith(['http://good.com:8080/reset', 'good.com'])]
    #[TestWith(['//good.com/reset', null], 'protocol-relative has no scheme')]
    #[TestWith(['good.com/reset', null], 'missing scheme is not a host at all')]
    #[TestWith(['javascript:alert(1)', null])]
    #[TestWith([null, null])]
    #[TestWith(['', null])]
    public function host_of_extracts_the_lowercased_host_or_null(?string $url, ?string $expectedHost): void
    {
        $this->assertSame($expectedHost, AllowedResetUrlHost::hostOf($url));
    }
}
