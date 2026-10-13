<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit\Services;

use Binaryk\LaravelRestify\Services\Auth\TokenTtlResolver;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class TokenTtlResolverTest extends TestCase
{
    #[Test]
    #[TestWith([null, null], 'null never expires')]
    #[TestWith(['', null], 'an empty string never expires')]
    #[TestWith([false, null], 'false never expires')]
    #[TestWith([0, null], 'zero never expires')]
    #[TestWith(['0', null], 'a zero string never expires')]
    #[TestWith([0.0, null], 'a zero float never expires')]
    #[TestWith([60, 3600], 'an int is minutes')]
    #[TestWith(['60', 3600], 'a numeric string is minutes')]
    #[TestWith([1.5, 90], 'a float is minutes')]
    #[TestWith(['0.5', 30], 'a sub-minute ttl is rounded to seconds')]
    #[TestWith(['0.01', 1], 'the smallest ttl that rounds to a whole second')]
    public function it_resolves_the_ttl_to_seconds(int|float|string|bool|null $configured, ?int $expectedSeconds): void
    {
        $this->assertSame($expectedSeconds, TokenTtlResolver::seconds($configured));
    }

    #[Test]
    #[TestWith([-5], 'negative')]
    #[TestWith(['abc'], 'non-numeric')]
    #[TestWith(['60m'], 'unit suffix')]
    #[TestWith([' '], 'blank')]
    #[TestWith([true], 'boolean')]
    #[TestWith([[60]], 'array')]
    #[TestWith(['0.001'], 'rounds to zero seconds')]
    #[TestWith([0.001], 'float rounds to zero seconds')]
    #[TestWith(['1e999'], 'infinite string')]
    #[TestWith(['1e18'], 'overflows an int of seconds')]
    #[TestWith([NAN], 'NAN')]
    #[TestWith([INF], 'INF')]
    public function it_rejects_anything_that_is_not_a_positive_number_of_minutes(mixed $configured): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('restify.auth.token_ttl');

        TokenTtlResolver::seconds($configured);
    }

    #[Test]
    public function the_message_names_the_offending_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"60m"');

        TokenTtlResolver::seconds('60m');
    }
}
