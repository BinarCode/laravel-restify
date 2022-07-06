<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Models\ActionLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\Fluent\AssertableJson;

class RepositoryStoreControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();
    }

    public function test_store_basic_validation_works(): void
    {
        $this->postJson(PostRepository::route(), [])
            ->assertStatus(422);
    }

    public function test_unauthorized_store(): void
    {
        $_SERVER['restify.post.store'] = false;

        Gate::policy(Post::class, PostPolicy::class);

        $this->postJson(PostRepository::route(), [
            'title' => 'Title',
            'description' => 'Title',
        ])->assertStatus(403);
    }

    public function test_success_storing(): void
    {
        $_SERVER['restify.post.store'] = true;

        $this->postJson(PostRepository::route(), $data = [
            'user_id' => ($user = $this->mockUsers()->first())->id,
            'title' => $title = 'Some post title',
        ])->assertCreated()->assertHeader('Location', PostRepository::route(1))
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->where('data.attributes.title', $title)
                    ->where('data.attributes.user_id', 1)
                    ->where('data.id', '1')
                    ->where('data.type', PostRepository::uriKey())
            );

        $this->assertDatabaseHas('posts', $data);
    }

    public function test_will_store_only_defined_fields_from_fieldsForStore(): void
    {
        $user = $this->mockUsers()->first();

        $this->postJson(PostRepository::route(), [
            'user_id' => $user->getKey(),
            'title' => 'Some post title',
            'description' => 'A very short description',
        ])->assertCreated()
            ->assertHeader('Location', PostRepository::route(1))
            ->assertJson(fn(AssertableJson $json) => $json
                ->missing('data.attributes.description')
                ->where('data.attributes.title', 'Some post title')
                ->etc()
            );
    }

    public function test_cannot_store_unauthorized_fields(): void
    {
        PostRepository::partialMock()
            ->shouldReceive('fieldsForStore')
            ->andreturn([
                Field::new('title'),

                Field::new('description')->canStore(fn() => false),
            ]);

        $this->postJson(PostRepository::route(), [
            'description' => 'Description',
            'title' => $updated = 'Title',
        ])
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->where('data.attributes.title', $updated)
                    ->where('data.attributes.description', null)
                    ->etc()
            );
    }

    public function test_cannot_store_readonly_fields(): void
    {
        PostRepository::partialMock()
            ->shouldReceive('fieldsForStore')
            ->andreturn([
                Field::new('title'),

                Field::new('description')->readonly(),
            ]);

        $this->postJson(PostRepository::route(), [
            'description' => 'Description',
            'title' => $updated = 'Title',
        ])
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->where('data.attributes.title', $updated)
                    ->where('data.attributes.description', null)
                    ->etc()
            );
    }

    public function test_storing_repository_log_action(): void
    {
        $this->authenticate();

        $this->postJson(PostRepository::route(), $data = [
            'title' => 'Some post title',
        ])->assertCreated();

        $this->assertDatabaseHas('action_logs', [
            'user_id' => $this->authenticatedAs->getAuthIdentifier(),
            'name' => ActionLog::ACTION_CREATED,
            'actionable_type' => Post::class,
        ]);

        $log = ActionLog::latest()->first();

        $this->assertSame($data, $log->changes);
    }
}
