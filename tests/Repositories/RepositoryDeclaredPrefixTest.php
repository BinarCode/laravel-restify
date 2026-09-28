<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\DeclaredPrefix\DeclaredPrefixPostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\DeclaredPrefix\FirstInheritedPrefixRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\DeclaredPrefix\SecondInheritedPrefixRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class RepositoryDeclaredPrefixTest extends IntegrationTestCase
{
    #[Test]
    public function mounting_applies_the_declared_prefix(): void
    {
        Restify::repositories([DeclaredPrefixPostRepository::class]);

        $this->assertSame('api/v2', DeclaredPrefixPostRepository::prefix());
    }

    #[Test]
    #[TestWith(['api/v3', 'api/v3'], 'another prefix')]
    #[TestWith([null, null], 'no prefix')]
    public function a_prefix_set_before_mounting_wins_over_the_declared_one(?string $prefix, ?string $expected): void
    {
        DeclaredPrefixPostRepository::setPrefix($prefix);

        Restify::repositories([DeclaredPrefixPostRepository::class]);

        $this->assertSame($expected, DeclaredPrefixPostRepository::prefix());
    }

    #[Test]
    public function setting_the_prefix_leaves_a_sibling_on_the_prefix_both_inherit(): void
    {
        FirstInheritedPrefixRepository::setPrefix('api/v3');

        Restify::repositories([
            FirstInheritedPrefixRepository::class,
            SecondInheritedPrefixRepository::class,
        ]);

        $this->assertSame('api/v3', FirstInheritedPrefixRepository::prefix());
        $this->assertSame('api/v2', SecondInheritedPrefixRepository::prefix());
    }
}
