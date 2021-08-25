<?php

namespace Binaryk\LaravelRestify\Tests\Actions;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Testing\Fluent\AssertableJson;

class FieldActionTest extends IntegrationTest
{
    /** * @test */
    public function can_use_actionable_field(): void
    {
        PostRepository::partialMock()
            ->shouldReceive('fieldsForStore')
            ->andreturn([
                Field::new('title'),



                Field::new('description')->canStore(fn() => false),
            ]);

        $this
            ->withoutExceptionHandling()
            ->postJson(PostRepository::to(), [
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
}
