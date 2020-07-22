<?php

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class RepositoryCustomPrefixTest extends IntegrationTest
{
    protected function setUp(): void
    {
        PostRepository::$prefix = 'api/restify-api/v1';

        parent::setUp();
    }

    public function test_repository_can_have_custom_prefix()
    {
        $this->getJson('api/restify-api/v1/'.PostRepository::uriKey())
            ->dump()
            ->assertSuccessful();
    }
}
