<?php

namespace Binaryk\LaravelRestify\Tests\Controllers\Show;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Filters\RelatedDto;
use Binaryk\LaravelRestify\Tests\Database\Factories\CommentFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\Comment\CommentRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

class ShowRelatedFeatureTest extends IntegrationTestCase
{
    use RefreshDatabase;

    public function test_show_related_doesnt_load_for_nested_relationships_that_didnt_require_it(): void
    {
        CommentRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                BelongsTo::make('user'),
                BelongsTo::make('post'),
            ]);

        PostRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                BelongsTo::make('user'),
            ]);

        $comment = CommentFactory::one();

        $this->withoutExceptionHandling();

        $this->getJson(CommentRepository::route($comment, query: [
            'related' => 'user, post.user',
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->has('data.relationships.user')
                ->has('data.relationships.post')
                ->where('included.0.type', 'users')
                ->etc()
        );

        app(RelatedDto::class)->reset();

        $this->getJson(CommentRepository::route($comment, query: [
            'related' => 'user, post',
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->has('data.relationships.user')
                ->has('data.relationships.post')
                ->missing('data.relationships.post.relationships.user')
                ->etc()
        );
    }
}
