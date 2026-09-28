<?php

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

class RepositoryEventsTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    public function test_booted_method_not_invoked_when_foreign_repository(): void
    {
        $this->getJson(PostRepository::route());

        $this->assertFalse(UserRepository::$wasBooted);
    }

    public function test_booted_method_invoked(): void
    {
        $this->getJson(UserRepository::route());

        $this->assertTrue(UserRepository::$wasBooted);
    }
}
