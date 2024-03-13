<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

class RepositoryUpdateBulkControllerTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    public function test_basic_update_validation_works(): void
    {
        $post = Post::factory()->create([
            'user_id' => 1,
            'title' => 'First title',
        ]);

        $this->postJson(PostRepository::route('bulk/update'), [
            [
                'data' => [
                    'id' => $post->id,
                    'attributes' => [
                        'title' => null,
                    ],
                ]
            ],
        ])->assertStatus(422);
    }

    public function test_basic_update_works(): void
    {
        $post1 = Post::factory()->create([
            'user_id' => 1,
            'title' => 'First title',
        ]);
        $post2 = Post::factory()->create([
            'user_id' => 1,
            'title' => 'Second title',
        ]);

        $this->postJson(PostRepository::route('bulk/update'), [
            [
                'data' => [
                    'id' => $post1->id,
                    'attributes' => [
                        'title' => 'Updated first title',
                    ],
                ]
            ],
            [
                'data' => [
                    'id' => $post2->id,
                    'attributes' => [
                        'title' => 'Updated second title',
                    ],
                ]
            ],
        ])
            ->assertOk();

        $updatedPost = Post::find($post1->id);
        $updatedPost2 = Post::find($post2->id);

        $this->assertEquals($updatedPost->title, 'Updated first title');
        $this->assertEquals($updatedPost2->title, 'Updated second title');
    }
}
