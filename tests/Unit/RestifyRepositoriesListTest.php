<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

class RestifyRepositoriesListTest extends IntegrationTestCase
{
    /** @var list<class-string<Repository>> */
    private array $originalRepositories;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalRepositories = Restify::$repositories;
    }

    protected function tearDown(): void
    {
        Restify::$repositories = $this->originalRepositories;

        parent::tearDown();
    }

    #[Test]
    public function re_registering_an_already_registered_repository_keeps_the_list_sequential(): void
    {
        Restify::$repositories = [
            UserRepository::class,
            PostRepository::class,
        ];

        Restify::repositories([
            UserRepository::class,
            CompanyRepository::class,
        ]);

        $this->assertTrue(array_is_list(Restify::$repositories));

        $this->assertSame(
            [UserRepository::class, PostRepository::class, CompanyRepository::class],
            Restify::$repositories,
        );
    }
}
