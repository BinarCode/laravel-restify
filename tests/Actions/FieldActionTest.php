<?php

namespace Binaryk\LaravelRestify\Tests\Actions;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\Test;

class FieldActionTest extends IntegrationTestCase
{
    #[Test]
    public function can_use_actionable_field(): void
    {
        $action = new class extends Action
        {
            public bool $showOnShow = true;

            public function handle(RestifyRequest $request, Post $post)
            {
                $description = $request->input('description');

                $post->update([
                    'description' => 'Actionable '.$description,
                ]);
            }
        };

        PostRepository::partialMock()
            ->shouldReceive('fieldsForStore')
            ->andreturn([
                Field::new('title'),

                Field::new('description')->action($action),
            ]);

        $this
            ->postJson(PostRepository::route(), [
                'description' => 'Description',
                'title' => $updated = 'Title',
            ])
            ->assertCreated()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('data.attributes.title', $updated)
                    ->where('data.attributes.description', 'Actionable Description')
                    ->etc()
            );

    }

    #[Test]
    public function can_use_actionable_field_on_bulk_store(): void
    {
        $action = new class extends Action
        {
            public bool $showOnShow = true;

            public function handle(RestifyRequest $request, Post $post, int $row)
            {
                $description = data_get($request[$row], 'description');

                $post->update([
                    'description' => 'Actionable '.$description,
                ]);
            }
        };

        PostRepository::partialMock()
            ->shouldReceive('fieldsForStoreBulk')
            ->andreturn([
                Field::new('title'),

                Field::new('description')->action($action),
            ]);

        $this->postJson(PostRepository::route('bulk'), [
            [
                'title' => $title1 = 'First title',
                'description' => 'first description',
            ],
            [
                'title' => $title2 = 'Second title',
                'description' => 'second description',
            ],
        ])
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('data.0.title', $title1)
                    ->where('data.0.description', 'Actionable first description')
                    ->where('data.1.title', $title2)
                    ->where('data.1.description', 'Actionable second description')
                    ->etc()
            );
    }

    #[Test]
    public function can_use_actionable_field_on_bulk_update(): void
    {
        $action = new class extends Action
        {
            public bool $showOnShow = true;

            public function handle(RestifyRequest $request, Post $post, int $row)
            {
                $description = data_get($request[$row], 'description');

                $post->update([
                    'description' => 'Actionable '.$description,
                ]);
            }
        };

        PostRepository::partialMock()
            ->shouldReceive('fieldsForUpdateBulk')
            ->andreturn([
                Field::new('title'),

                Field::new('description')->action($action),
            ]);

        $postId1 = $this
            ->withoutExceptionHandling()
            ->postJson(PostRepository::route(), [
                'title' => 'First title',
            ])->json('data.id');

        $postId2 = $this
            ->withoutExceptionHandling()
            ->postJson(PostRepository::route(), [
                'title' => 'Second title',
            ])->json('data.id');

        $this
            ->withoutExceptionHandling()
            ->postJson(PostRepository::route('bulk/update'), [
                [
                    'id' => $postId1,
                    'description' => 'first description',
                ],
                [
                    'id' => $postId2,
                    'description' => 'second description',
                ],
            ])->assertOk();

        $this->assertSame(
            'Actionable first description',
            Post::find($postId1)->description
        );

        $this->assertSame(
            'Actionable second description',
            Post::find($postId2)->description
        );
    }
}
