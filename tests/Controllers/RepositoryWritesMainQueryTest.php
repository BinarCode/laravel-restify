<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Fields\Image;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class RepositoryWritesMainQueryTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    #[Test]
    #[TestWith(['putJson'], 'update')]
    #[TestWith(['patchJson'], 'patch')]
    public function an_update_cannot_reach_a_record_hidden_by_main_query(string $method): void
    {
        [$hidden, $visible] = $this->hidePost();

        $this->{$method}(PostRepository::route($hidden), ['title' => 'Changed'])->assertNotFound();
        $this->{$method}(PostRepository::route($visible), ['title' => 'Changed'])->assertOk();

        $this->assertDatabaseHas(Post::class, ['id' => $hidden->getKey(), 'title' => 'Original']);
        $this->assertDatabaseHas(Post::class, ['id' => $visible->getKey(), 'title' => 'Changed']);
    }

    #[Test]
    public function a_delete_cannot_reach_a_record_hidden_by_main_query(): void
    {
        [$hidden, $visible] = $this->hidePost();

        $this->deleteJson(PostRepository::route($hidden))->assertNotFound();
        $this->deleteJson(PostRepository::route($visible))->assertNoContent();

        $this->assertModelExists($hidden);
        $this->assertModelMissing($visible);
    }

    #[Test]
    public function a_bulk_update_with_a_hidden_record_writes_nothing(): void
    {
        [$hidden, $visible] = $this->hidePost();

        $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => $visible->getKey(), 'title' => 'Changed'],
            ['id' => $hidden->getKey(), 'title' => 'Changed'],
        ])->assertNotFound();

        $this->assertDatabaseHas(Post::class, ['id' => $hidden->getKey(), 'title' => 'Original']);
        $this->assertDatabaseHas(Post::class, ['id' => $visible->getKey(), 'title' => 'Original']);
    }

    #[Test]
    public function a_bulk_delete_with_a_hidden_record_deletes_nothing(): void
    {
        [$hidden, $visible] = $this->hidePost();

        $this->deleteJson(PostRepository::route('bulk/delete'), [
            $visible->getKey(),
            $hidden->getKey(),
        ])->assertNotFound();

        $this->assertModelExists($hidden);
        $this->assertModelExists($visible);
    }

    #[Test]
    public function a_field_delete_cannot_reach_a_record_hidden_by_main_query(): void
    {
        Storage::fake('customDisk');

        $users = $this->mockUsers(2)->each(function (User $user): void {
            $user->avatar = UploadedFile::fake()->image('image.jpg')->storeAs('/', "avatar-{$user->getKey()}.jpg", 'customDisk');
            $user->save();
        });
        [$hidden, $visible] = [$users->first(), $users->last()];

        $_SERVER['restify.user.mainQueryCallback'] = fn ($query) => $query->clone()->whereKeyNot($hidden->getKey());

        UserRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Image::make('avatar')->disk('customDisk')->deletable(true),
            ]);

        $this->deleteJson(UserRepository::route($hidden->getKey().'/field/avatar'))->assertNotFound();
        $this->deleteJson(UserRepository::route($visible->getKey().'/field/avatar'))->assertNoContent();

        Storage::disk('customDisk')->assertExists("avatar-{$hidden->getKey()}.jpg");
        Storage::disk('customDisk')->assertMissing("avatar-{$visible->getKey()}.jpg");
    }

    #[Test]
    public function the_opt_out_restores_the_policy_only_behaviour(): void
    {
        config()->set('restify.repositories.apply_main_query_to_writes', false);

        [$hidden] = $this->hidePost();

        $this->patchJson(PostRepository::route($hidden), ['title' => 'Changed'])->assertOk();
        $this->deleteJson(PostRepository::route($hidden))->assertNoContent();

        $this->assertModelMissing($hidden);
    }

    /**
     * Create two posts and hide the first one through PostRepository::mainQuery(). The callback
     * returns a clone, so a caller that ignores the builder mainQuery returns still sees both.
     *
     * @return array{Post, Post}
     */
    private function hidePost(): array
    {
        $hidden = Post::factory()->create(['user_id' => 1, 'title' => 'Original']);
        $visible = Post::factory()->create(['user_id' => 1, 'title' => 'Original']);

        $_SERVER['restify.post.mainQueryCallback'] = fn ($query) => $query->clone()->whereKeyNot($hidden->getKey());

        return [$hidden, $visible];
    }
}
