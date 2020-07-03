<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class RepositoryUpdateBulkControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    public function test_basic_update_validation_works()
    {
        $post1 = factory(Post::class)->create([
            'user_id' => 1,
            'title' => 'First title',
        ]);

        $this->post('/restify-api/posts/bulk/update', [
            [
                'id' => $post1->id,
                'title' => null,
            ],
        ])
            ->assertStatus(400)
            ->assertJson([
                'errors' => [
                    [
                        '0.title' => [
                            'This field is required',
                        ],
                    ],
                ],
            ]);
    }

    public function test_basic_update_works()
    {
        $post1 = factory(Post::class)->create([
            'user_id' => 1,
            'title' => 'First title',
        ]);
        $post2 = factory(Post::class)->create([
            'user_id' => 1,
            'title' => 'Second title',
        ]);

        $this->post('/restify-api/posts/bulk/update', [
            [
                'id' => $post1->id,
                'title' => 'Updated first title',
            ],
            [
                'id' => $post2->id,
                'title' => 'Updated second title',
            ]
        ])
            ->assertStatus(200);

        $updatedPost = Post::find($post1->id);
        $updatedPost2 = Post::find($post2->id);

        $this->assertEquals($updatedPost->title, 'Updated first title');
        $this->assertEquals($updatedPost2->title, 'Updated second title');
    }
}
