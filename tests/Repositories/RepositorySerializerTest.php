<?php

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Tests\Database\Factories\PostFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Testing\Fluent\AssertableJson;

class RepositorySerializerTest extends IntegrationTest
{
    public function test_can_manually_serialize_repository(): void
    {
        PostFactory::many(20, [
            'title' => 'Title',
        ]);

        PostRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                'user' => BelongsTo::make('user', UserRepository::class),
            ]);

        $response = rest(Post::all())
            ->related('user')
            ->sortDesc('id')
            ->perPage(20)
            ->jsonSerialize();

        $assertable = AssertableJson::fromArray($response);

        $assertable
            ->has('meta')
            ->has('data')
            ->count('data', 20)
            ->etc();
    }

    public function test_disable_show_meta(): void
    {
        $posts = PostFactory::many();

        config()->set('restify.repositories.serialize_show_meta', false);

        $this->getJson(PostRepository::route($posts->first()->id))
            ->assertJson(fn(AssertableJson $json) => $json
                ->missing('data.meta')
                ->etc()
            );

        $this->getJson(PostRepository::route($posts->first()->id, [
            'withMeta' => true,
        ]))
            ->assertJson(fn(AssertableJson $json) => $json
                ->has('data.meta')
                ->etc()
            );
    }

    public function test_disable_index_meta(): void
    {
        PostFactory::many();

        config()->set('restify.repositories.serialize_index_meta', false);

        $this->getJson(PostRepository::route())
            ->assertJson(fn(AssertableJson $json) => $json
                ->missing('data.0.meta')
                ->etc()
            );

        $this->getJson(PostRepository::route(query: [
            'withMeta' => true,
        ]))
            ->assertJson(fn(AssertableJson $json) => $json
                ->has('data.0.meta')
                ->etc()
            );
    }
}
