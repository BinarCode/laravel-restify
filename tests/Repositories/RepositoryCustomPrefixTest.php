<?php

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class RepositoryCustomPrefixTest extends IntegrationTest
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
}
