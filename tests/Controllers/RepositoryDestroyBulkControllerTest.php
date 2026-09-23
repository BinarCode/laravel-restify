<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;

class RepositoryDestroyBulkControllerTest extends IntegrationTestCase
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

        $this->deleteJson(PostRepository::route('bulk/delete'), [
            $post1->getKey(),
            $post2->getKey(),
        ])->assertOk();

        $this->assertModelMissing($post1);
        $this->assertModelMissing($post2);
        $this->assertModelExists($post3);
    }

    #[Test]
    public function a_missing_key_is_rejected_and_nothing_is_deleted(): void
    {
        Gate::policy(Post::class, PostPolicy::class);

        $post1 = Post::factory()->create();
        $post2 = Post::factory()->create();

        $this->deleteJson(PostRepository::route('bulk/delete'), [
            $post1->getKey(),
            null,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('keys.1');

        $this->assertDatabaseHas(Post::class, ['id' => $post1->id]);
        $this->assertDatabaseHas(Post::class, ['id' => $post2->id]);
    }
}
