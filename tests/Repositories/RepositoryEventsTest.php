<?php

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class RepositoryEventsTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();

        Repository::clearBootedRepositories();
    }

    public function test_booted_method_not_invoked_when_foreign_repository()
    {
        UserRepository::$wasBooted = false;

        $this->getJson('posts');

        $this->assertFalse(UserRepository::$wasBooted);
    }

    public function test_booted_method_invoked(): void
    {
        UserRepository::$wasBooted = false;

        $this->getJson(UserRepository::to());

        $this->assertTrue(UserRepository::$wasBooted);
    }
}
