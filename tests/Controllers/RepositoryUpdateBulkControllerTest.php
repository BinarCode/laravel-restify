<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
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
        [$post1, $post2] = Post::factory()
            ->count(2)
            ->sequence(
                ['title' => 'First title'],
                ['title' => 'Second title'],
            )
            ->create(['user_id' => 1]);

        $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => $post1->id, 'title' => 'Updated first title'],
            ['id' => $post2->id, 'title' => 'Updated second title'],
        ])->assertOk();

        $this->assertEquals('Updated first title', $post1->fresh()->title);
        $this->assertEquals('Updated second title', $post2->fresh()->title);
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

    public function test_bulk_update_only_owner_can_update_posts(): void
    {
        [$owner, $otherUser] = User::factory()->count(2)->create();

        [$post1, $post2] = Post::factory()
            ->count(2)
            ->sequence(
                ['title' => 'Post 1'],
                ['title' => 'Post 2'],
            )
            ->create(['user_id' => $owner->id]);

        $_SERVER['restify.post.updateBulk.callback'] = static fn (User $user, Post $post): bool => $post->user_id === $user->id;

        $this->actingAs($owner);
        $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => $post1->id, 'title' => 'Updated Post 1'],
            ['id' => $post2->id, 'title' => 'Updated Post 2'],
        ])->assertOk();

        $this->assertEquals('Updated Post 1', $post1->fresh()->title);
        $this->assertEquals('Updated Post 2', $post2->fresh()->title);

        $this->actingAs($otherUser);
        $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => $post1->id, 'title' => 'Hacked by other'],
        ])->assertForbidden();

        unset($_SERVER['restify.post.updateBulk.callback']);
    }

    public function test_bulk_update_policy_checks_post_owner_relation(): void
    {
        [$verifiedOwner, $unverifiedOwner, $actingUser] = User::factory()
            ->count(3)
            ->sequence(
                ['email' => 'verified@example.com'],
                ['email' => 'unverified@test.com'],
                ['email' => 'acting@test.com'],
            )
            ->create();

        [$postByVerified, $postByUnverified] = Post::factory()
            ->count(2)
            ->sequence(
                ['user_id' => $verifiedOwner->id, 'title' => 'Verified Post'],
                ['user_id' => $unverifiedOwner->id, 'title' => 'Unverified Post'],
            )
            ->create();

        $_SERVER['restify.post.updateBulk.callback'] = static fn (User $user, Post $post): bool => str_ends_with($post->user->email, '@example.com');

        $this->actingAs($actingUser);

        $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => $postByVerified->id, 'title' => 'Updated Verified Post'],
        ])->assertOk();

        $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => $postByUnverified->id, 'title' => 'Updated Unverified Post'],
        ])->assertForbidden();

        unset($_SERVER['restify.post.updateBulk.callback']);
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
