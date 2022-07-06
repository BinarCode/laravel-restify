<?php

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Support\Facades\Gate;

class RepositoryCustomPrefixTest extends IntegrationTest
{
    protected function setUp(): void
    {
        PostRepository::setPrefix('api/v1');

        PostRepository::setIndexPrefix('api/index');

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        PostRepository::$prefix = null;
        PostRepository::$indexPrefix = null;
    }

    public function test_repository_can_have_custom_prefix(): void
    {
        $this
            ->getJson('api/index/'.PostRepository::uriKey())
            ->assertSuccessful();
    }

    public function test_repository_prefix_block_default_route(): void
    {
        $this->getJson(PostRepository::uriKey())
            ->assertForbidden();

        $this->getJson('api/index/'.PostRepository::uriKey())
            ->assertSuccessful();

        $this->postJson(PostRepository::uriKey())
            ->assertForbidden();
    }
}
