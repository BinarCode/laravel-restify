<?php

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class RepositoryCustomPrefixTest extends IntegrationTest
{
    protected function setUp(): void
    {
        PostRepository::$prefix = 'api/restify-api/v1';

        PostRepository::$indexPrefix = 'api/restify-api/index';

        parent::setUp();
    }

    public function test_repository_can_have_custom_prefix()
    {
        $this->getJson('api/restify-api/index/'.PostRepository::uriKey())
            ->assertSuccessful();
    }

    public function test_repository_prefix_block_default_route()
    {
        $this->getJson('/restify-api/'.PostRepository::uriKey())
            ->assertForbidden();

        $this->getJson('api/restify-api/index/'.PostRepository::uriKey())
            ->assertSuccessful();

        $this->postJson('/restify-api/'.PostRepository::uriKey())
            ->assertForbidden();
    }
}
