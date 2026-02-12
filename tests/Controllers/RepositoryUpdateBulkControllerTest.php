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
                'id' => $post->id,
                'title' => null,
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

    public function test_bulk_update_validation_reports_correct_indices(): void
    {
        $posts = Post::factory()
            ->count(3)
            ->sequence(
                ['title' => 'First title'],
                ['title' => 'Second title'],
                ['title' => 'Third title'],
            )
            ->create(['user_id' => 1]);

        $response = $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => $posts[0]->id, 'title' => 'Valid updated title'],  // Valid (index 0)
            ['id' => $posts[1]->id, 'title' => null],                   // Invalid (index 1)
            ['id' => $posts[2]->id, 'title' => null],                   // Invalid (index 2)
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['1.title', '2.title']);
        $response->assertJsonMissingValidationErrors(['0.title']);
    }
}
