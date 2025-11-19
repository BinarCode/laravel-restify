<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;

class RepositoryStoreControllerTest extends IntegrationTestCase
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

        $this
            ->posts()
            ->create(tap: function (TestResponse $response) {
                $response->assertForbidden();
            });
    }

    public function test_success_storing(): void
    {
        $_SERVER['restify.post.store'] = true;

        $post = $this
            ->posts()
            ->fake()
            ->attributes([
                'title' => $title = 'Some post title',
            ])
            ->create(tap: fn (TestResponse $testResponse) => $testResponse
                ->assertHeader('Location', PostRepository::route(1))
                ->assertJson(
                    fn (AssertableJson $json) => $json
                        ->where('data.attributes.title', $title)
                        ->where('data.attributes.user_id', 1)
                        ->where('data.id', '1')
                        ->where('data.type', PostRepository::uriKey()),
                ))
            ->model();

        $this->assertModelExists($post);
    }

    public function test_will_store_only_defined_fields_from_fields_for_store(): void
    {
        $user = $this->mockUsers()->first();

        $this->postJson(PostRepository::route(), [
            'user_id' => $user->getKey(),
            'title' => 'Some post title',
            'description' => 'A very short description',
        ])->assertCreated()
            ->assertHeader('Location', PostRepository::route(1))
            ->assertJson(
                fn (AssertableJson $json) => $json
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

                Field::new('description')->canStore(fn () => false),
            ]);

        $this->postJson(PostRepository::route(), [
            'description' => 'Description',
            'title' => $updated = 'Title',
        ])
            ->assertJson(
                fn (AssertableJson $json) => $json
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
                fn (AssertableJson $json) => $json
                    ->where('data.attributes.title', $updated)
                    ->where('data.attributes.description', null)
                    ->etc()
            );
    }
}
