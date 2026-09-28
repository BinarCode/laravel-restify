<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;

#[RunTestsInSeparateProcesses]
class RepositoryPrefixIsolationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        PostRepository::setPrefix('api/v1');

        parent::setUp();
    }

    #[Test]
    public function the_prefixed_repository_moves_under_its_prefix(): void
    {
        $this->assertSame('api/v1', PostRepository::prefix());

        $this->getJson('api/v1/posts')->assertOk();
        $this->getJson(Restify::path(PostRepository::uriKey()))->assertForbidden();
    }

    #[Test]
    public function repositories_mounted_afterwards_keep_the_default_prefix(): void
    {
        $this->assertNull(UserRepository::prefix());
        $this->assertNull(CompanyRepository::prefix());
        $this->assertSame(Restify::path(UserRepository::uriKey()), UserRepository::route());
        $this->assertTrue(UserRepository::authorizedToUseRoute(
            RestifyRequest::create(Restify::path(UserRepository::uriKey()), 'GET'),
        ));
    }

    #[Test]
    public function repositories_mounted_afterwards_keep_their_default_routes(): void
    {
        $this->getJson(Restify::path(UserRepository::uriKey()))->assertOk();
        $this->getJson(Restify::path(CompanyRepository::uriKey()))->assertOk();

        $this->getJson('api/v1/users')->assertNotFound();
        $this->getJson('api/v1/companies')->assertNotFound();
    }
}
