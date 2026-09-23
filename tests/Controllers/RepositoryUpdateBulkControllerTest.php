<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class RepositoryUpdateBulkControllerTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    #[Test]
    public function a_row_missing_id_is_rejected_and_nothing_is_written(): void
    {
        $post1 = Post::factory()->create(['user_id' => 1, 'title' => 'Original 1']);
        $post2 = Post::factory()->create(['user_id' => 1, 'title' => 'Original 2']);

        $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => $post1->id, 'title' => 'Updated 1'],
            ['title' => 'Updated 2 (no id)'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('1.id');

        $this->assertDatabaseHas(Post::class, ['id' => $post1->id, 'title' => 'Original 1']);
        $this->assertDatabaseHas(Post::class, ['id' => $post2->id, 'title' => 'Original 2']);
    }

    #[Test]
    public function a_row_with_a_null_id_is_rejected(): void
    {
        $post = Post::factory()->create(['user_id' => 1, 'title' => 'Original']);

        $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => null, 'title' => 'Updated'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('0.id');

        $this->assertDatabaseHas(Post::class, ['id' => $post->id, 'title' => 'Original']);
    }

    #[Test]
    #[TestWith([false], 'matching integers')]
    #[TestWith([true], "'3' vs 3")]
    public function duplicate_ids_in_the_same_payload_are_rejected(bool $secondIdAsString): void
    {
        $post = Post::factory()->create(['user_id' => 1, 'title' => 'Original']);

        $secondId = $secondIdAsString ? (string) $post->id : $post->id;

        $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => $post->id, 'title' => 'First'],
            ['id' => $secondId, 'title' => 'Second'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['0.id', '1.id']);

        $this->assertDatabaseHas(Post::class, ['id' => $post->id, 'title' => 'Original']);
    }

    #[Test]
    #[TestWith([[1]], 'array id')]
    #[TestWith([['id' => 1]], 'nested object id')]
    #[TestWith([true], 'boolean')]
    public function a_non_scalar_id_is_rejected_instead_of_erroring(mixed $id): void
    {
        $post = Post::factory()->create(['user_id' => 1, 'title' => 'Original']);

        $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => $id, 'title' => 'Updated'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('0.id');

        $this->assertDatabaseHas(Post::class, ['id' => $post->id, 'title' => 'Original']);
    }

    #[Test]
    public function a_numeric_string_id_is_accepted_and_the_row_is_updated(): void
    {
        $post = Post::factory()->create(['user_id' => 1, 'title' => 'Original']);

        $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => (string) $post->id, 'title' => 'Updated via string id'],
        ])->assertOk();

        $this->assertDatabaseHas(Post::class, ['id' => $post->id, 'title' => 'Updated via string id']);
    }

    #[Test]
    public function a_repositorys_own_id_rules_are_kept_alongside_the_required_and_distinct_guard(): void
    {
        $post = Post::factory()->create(['user_id' => 1, 'title' => 'Original']);

        $_SERVER['restify.post.id.updateBulkRules'] = 'prohibited';

        try {
            $this->postJson(PostRepository::route('bulk/update'), [
                ['id' => $post->id, 'title' => 'n'],
                ['title' => 'x'],
            ])->assertUnprocessable()
                ->assertJsonValidationErrors(['0.id', '1.id']);

            $this->assertDatabaseHas(Post::class, ['id' => $post->id, 'title' => 'Original']);
        } finally {
            unset($_SERVER['restify.post.id.updateBulkRules']);
        }
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
}
