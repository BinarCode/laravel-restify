<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\DeclaredPrefixPostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Each test runs in its own process because mounting writes the prefix into
 * static state that would outlive the test.
 */
#[RunTestsInSeparateProcesses]
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
    public function setting_the_prefix_leaves_the_declared_one_of_another_repository_alone(): void
    {
        UserRepository::setPrefix('api/v1');

        Restify::repositories([DeclaredPrefixPostRepository::class]);

        $this->assertSame('api/v2', DeclaredPrefixPostRepository::prefix());
        $this->assertSame('api/v1', UserRepository::prefix());
    }
}
