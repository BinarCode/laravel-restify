<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class RepositoryUpdateBulkControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    public function test_basic_update_validation_works(): void
    {
        $post = factory(Post::class)->create([
            'user_id' => 1,
            'title' => 'First title',
        ]);

        $this->postJson(PostRepository::to('bulk/update'), [
            [
                'id' => $post->id,
                'title' => null,
            ],
        ])->assertStatus(422);
    }

    public function test_basic_update_works(): void
    {
        $post1 = factory(Post::class)->create([
            'user_id' => 1,
            'title' => 'First title',
        ]);
        $post2 = factory(Post::class)->create([
            'user_id' => 1,
            'title' => 'Second title',
        ]);

        $this->postJson('posts/bulk/update', [
            [
                'id' => $post1->id,
                'title' => 'Updated first title',
            ],
            [
                'id' => $post2->id,
                'title' => 'Updated second title',
            ],
        ])
            ->assertOk();

        $updatedPost = Post::find($post1->id);
        $updatedPost2 = Post::find($post2->id);

        $this->assertEquals($updatedPost->title, 'Updated first title');
        $this->assertEquals($updatedPost2->title, 'Updated second title');
    }
}
