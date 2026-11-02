<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Fields\HasMany;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

class RelatableRoutesAuthorizationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        UserRepository::$related = [
            'posts' => HasMany::make('posts', PostRepository::class),
        ];
    }

    #[Test]
    public function a_parent_hidden_by_main_query_answers_404_on_every_relatable_route(): void
    {
        [$hidden, $visible] = $this->hideUser();
        $hiddenPost = Post::factory()->create(['user_id' => $hidden->id, 'title' => 'Hidden']);
        $visiblePost = Post::factory()->create(['user_id' => $visible->id, 'title' => 'Visible']);

        $this->getJson(UserRepository::route("{$hidden->id}/posts"))->assertNotFound();
        $this->getJson(UserRepository::route("{$hidden->id}/posts/{$hiddenPost->id}"))->assertNotFound();
        $this->postJson(UserRepository::route("{$hidden->id}/posts"), ['title' => 'Created'])->assertNotFound();
        $this->postJson(UserRepository::route("{$hidden->id}/posts/{$hiddenPost->id}"), ['title' => 'Changed'])->assertNotFound();
        $this->putJson(UserRepository::route("{$hidden->id}/posts/{$hiddenPost->id}"), ['title' => 'Changed'])->assertNotFound();
        $this->deleteJson(UserRepository::route("{$hidden->id}/posts/{$hiddenPost->id}"))->assertNotFound();

        $this->assertSame(['Hidden'], $hidden->posts()->pluck('title')->all());

        $this->getJson(UserRepository::route("{$visible->id}/posts"))->assertOk()->assertJsonCount(1, 'data');
        $this->getJson(UserRepository::route("{$visible->id}/posts/{$visiblePost->id}"))->assertOk();
        $this->postJson(UserRepository::route("{$visible->id}/posts"), ['title' => 'Created'])->assertCreated();
        $this->putJson(UserRepository::route("{$visible->id}/posts/{$visiblePost->id}"), ['title' => 'Changed'])->assertOk();
        $this->deleteJson(UserRepository::route("{$visible->id}/posts/{$visiblePost->id}"))->assertNoContent();

        $this->assertSame(['Created'], $visible->posts()->pluck('title')->all());
    }

    #[Test]
    public function a_missing_parent_answers_404(): void
    {
        $this->getJson(UserRepository::route('999/posts'))->assertNotFound();
        $this->postJson(UserRepository::route('999/posts'), ['title' => 'Created'])->assertNotFound();

        $this->assertDatabaseCount('posts', 0);
    }

    #[Test]
    public function a_parent_the_show_policy_denies_answers_403_on_every_relatable_route(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id, 'title' => 'Kept']);
        $_SERVER['restify.users.show'] = false;

        $this->getJson(UserRepository::route("{$user->id}/posts"))->assertForbidden();
        $this->getJson(UserRepository::route("{$user->id}/posts/{$post->id}"))->assertForbidden();
        $this->postJson(UserRepository::route("{$user->id}/posts"), ['title' => 'Created'])->assertForbidden();
        $this->putJson(UserRepository::route("{$user->id}/posts/{$post->id}"), ['title' => 'Changed'])->assertForbidden();
        $this->deleteJson(UserRepository::route("{$user->id}/posts/{$post->id}"))->assertForbidden();

        $this->assertSame(['Kept'], $user->posts()->pluck('title')->all());
    }

    #[Test]
    public function writing_a_child_requires_the_parent_update_policy(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id, 'title' => 'Kept']);
        $_SERVER['restify.users.update'] = false;

        $this->postJson(UserRepository::route("{$user->id}/posts"), ['title' => 'Created'])->assertForbidden();
        $this->postJson(UserRepository::route("{$user->id}/posts/{$post->id}"), ['title' => 'Changed'])->assertForbidden();
        $this->putJson(UserRepository::route("{$user->id}/posts/{$post->id}"), ['title' => 'Changed'])->assertForbidden();
        $this->deleteJson(UserRepository::route("{$user->id}/posts/{$post->id}"))->assertForbidden();

        $this->assertSame(['Kept'], $user->posts()->pluck('title')->all());

        $this->getJson(UserRepository::route("{$user->id}/posts"))->assertOk()->assertJsonCount(1, 'data');
        $this->getJson(UserRepository::route("{$user->id}/posts/{$post->id}"))->assertOk();
    }

    #[Test]
    public function the_parent_is_checked_before_the_child_is_validated(): void
    {
        [$hidden] = $this->hideUser();
        $_SERVER['restify.users.show'] = false;

        $this->postJson(UserRepository::route("{$hidden->id}/posts"))->assertNotFound();

        $_SERVER['restify.user.mainQueryCallback'] = null;

        $this->postJson(UserRepository::route("{$hidden->id}/posts"))->assertForbidden();
    }

    #[Test]
    public function the_writes_opt_out_skips_the_parent_main_query_but_keeps_its_policy(): void
    {
        config()->set('restify.repositories.apply_main_query_to_writes', false);

        [$hidden] = $this->hideUser();

        $this->postJson(UserRepository::route("{$hidden->id}/posts"), ['title' => 'Created'])->assertCreated();
        $this->getJson(UserRepository::route("{$hidden->id}/posts"))->assertOk()->assertJsonCount(1, 'data');

        $_SERVER['restify.users.show'] = false;

        $this->getJson(UserRepository::route("{$hidden->id}/posts"))->assertForbidden();
    }

    /**
     * The callback returns a clone, so a caller that ignores the builder mainQuery returns still sees both.
     *
     * @return array{User, User}
     */
    private function hideUser(): array
    {
        $hidden = User::factory()->create();
        $visible = User::factory()->create();

        $_SERVER['restify.user.mainQueryCallback'] = fn ($query) => $query->clone()->whereKeyNot($hidden->getKey());

        return [$hidden, $visible];
    }
}
