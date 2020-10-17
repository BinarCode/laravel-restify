<?php

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class RepositoryCustomPrefixTest extends IntegrationTest
{
    protected function setUp(): void
    {
        PostRepository::$prefix = 'api/v1';

        PostRepository::$indexPrefix = 'api/index';

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        PostRepository::$prefix = null;
        PostRepository::$indexPrefix = null;
    }

    public function test_repository_can_have_custom_prefix()
    {
        $this->getJson('api/index/'.PostRepository::uriKey())
            ->assertSuccessful();
    }

    public function test_repository_prefix_block_default_route()
    {
        $this->getJson(PostRepository::uriKey())
            ->assertForbidden();

        $this->getJson('api/index/'.PostRepository::uriKey())
            ->assertSuccessful();

        $this->postJson(PostRepository::uriKey())
            ->assertForbidden();
    }
}
