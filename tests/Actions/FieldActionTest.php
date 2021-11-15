<?php

namespace Binaryk\LaravelRestify\Tests\Actions;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Testing\Fluent\AssertableJson;

class FieldActionTest extends IntegrationTest
{
    /** * @test */
    public function can_use_actionable_field(): void
    {
        $action = new class extends Action {
            public bool $showOnShow = true;

            public function handle(RestifyRequest $request, Post $post)
            {
                $description = $request->input('description');

                $post->update([
                    'description' => 'Actionable ' . $description,
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
            ->withoutExceptionHandling()
            ->postJson(PostRepository::to(), [
            'description' => 'Description',
            'title' => $updated = 'Title',
        ])
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('data.attributes.title', $updated)
                    ->where('data.attributes.description', 'Actionable Description')
                    ->etc()
            );
    }
}
