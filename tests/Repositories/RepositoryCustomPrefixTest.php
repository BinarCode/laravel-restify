<?php

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

class RepositoryCustomPrefixTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        PostRepository::setPrefix('api/v1');

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        PostRepository::setPrefix(null);
    }

    public function test_repository_can_have_custom_prefix(): void
    {
        $this
            ->withoutExceptionHandling()
            ->getJson(PostRepository::route())
            ->assertSuccessful();
    }

    public function test_repository_prefix_block_default_route(): void
    {
        $this->getJson(Restify::path(PostRepository::uriKey()))
            ->assertForbidden();

        $this->postJson(Restify::path(PostRepository::uriKey()))
            ->assertForbidden();

        $this->getJson(PostRepository::route())
            ->assertOk();

        $this->postJson(PostRepository::route(), ['title' => 'Title', 'user_id' => 1])
            ->assertCreated();
    }
}
