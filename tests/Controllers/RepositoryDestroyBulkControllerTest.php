<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Support\Facades\Gate;

class RepositoryDestroyBulkControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    public function test_basic_bulk_delete_works(): void
    {
        Gate::policy(Post::class, PostPolicy::class);

        $post1 = Post::factory()->create();
        $post2 = Post::factory()->create();
        $post3 = Post::factory()->create();

        $this->withoutExceptionHandling();

        $this->deleteJson(PostRepository::to('bulk/delete'), [
            $post1->getKey(),
            $post2->getKey(),
        ])->assertOk();

        $this->assertModelMissing($post1);
        $this->assertModelMissing($post2);
        $this->assertModelExists($post3);
    }
}
