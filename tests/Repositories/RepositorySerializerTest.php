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
}
