<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Actions;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class FieldActionAuthorizationTest extends IntegrationTestCase
{
    private const string FIELD_CAN_SEE = 'field-can-see';

    private const string ACTION_CAN_SEE = 'action-can-see';

    private const string ACTION_CAN_RUN = 'action-can-run';

    #[Test]
    #[TestWith([self::FIELD_CAN_SEE], 'field canSee')]
    #[TestWith([self::ACTION_CAN_SEE], 'action canSee')]
    #[TestWith([self::ACTION_CAN_RUN], 'action canRun')]
    public function store_with_a_denied_field_action_is_forbidden_and_rolled_back(string $denial): void
    {
        PostRepository::partialMock()
            ->shouldReceive('fieldsForStore')
            ->andReturn([
                Field::new('title'),
                $this->deniedDescriptionField($denial),
            ]);

        $this
            ->postJson(PostRepository::route(), [
                'title' => 'Title',
                'description' => 'Description',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount(Post::class, 0);
    }

    #[Test]
    public function store_runs_an_authorized_field_action_against_the_stored_model(): void
    {
        $authorizedModels = [];

        PostRepository::partialMock()
            ->shouldReceive('fieldsForStore')
            ->andReturn([
                Field::new('title'),
                $this->authorizedDescriptionField($authorizedModels),
            ]);

        $postId = $this
            ->postJson(PostRepository::route(), [
                'title' => 'Title',
                'description' => 'Description',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas(Post::class, [
            'id' => $postId,
            'description' => 'Actionable Description',
        ]);
        $this->assertSame([(int) $postId], $authorizedModels);
    }

    #[Test]
    #[TestWith([self::FIELD_CAN_SEE], 'field canSee')]
    #[TestWith([self::ACTION_CAN_SEE], 'action canSee')]
    #[TestWith([self::ACTION_CAN_RUN], 'action canRun')]
    public function store_bulk_with_a_denied_field_action_is_forbidden_and_rolled_back(string $denial): void
    {
        PostRepository::partialMock()
            ->shouldReceive('fieldsForStoreBulk')
            ->andReturn([
                Field::new('title'),
                $this->deniedDescriptionField($denial),
            ]);

        $this
            ->postJson(PostRepository::route('bulk'), [
                ['title' => 'First title', 'description' => 'first description'],
                ['title' => 'Second title', 'description' => 'second description'],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount(Post::class, 0);
    }

    #[Test]
    public function store_bulk_denied_on_a_later_row_rolls_back_the_earlier_rows(): void
    {
        $runs = 0;

        PostRepository::partialMock()
            ->shouldReceive('fieldsForStoreBulk')
            ->andReturn([
                Field::new('title'),
                Field::new('description')->action(
                    $this->descriptionAction()->canRun(function () use (&$runs): bool {
                        $runs++;

                        return $runs === 1;
                    })
                ),
            ]);

        $this
            ->postJson(PostRepository::route('bulk'), [
                ['title' => 'First title', 'description' => 'first description'],
                ['title' => 'Second title', 'description' => 'second description'],
            ])
            ->assertForbidden();

        $this->assertSame(2, $runs);
        $this->assertDatabaseCount(Post::class, 0);
    }

    #[Test]
    public function store_bulk_runs_an_authorized_field_action_against_each_stored_model(): void
    {
        $authorizedModels = [];

        PostRepository::partialMock()
            ->shouldReceive('fieldsForStoreBulk')
            ->andReturn([
                Field::new('title'),
                $this->authorizedDescriptionField($authorizedModels),
            ]);

        $postIds = $this
            ->postJson(PostRepository::route('bulk'), [
                ['title' => 'First title', 'description' => 'first description'],
                ['title' => 'Second title', 'description' => 'second description'],
            ])
            ->assertOk()
            ->json('data.*.id');

        $this->assertDatabaseHas(Post::class, [
            'id' => $postIds[0],
            'description' => 'Actionable first description',
        ]);
        $this->assertDatabaseHas(Post::class, [
            'id' => $postIds[1],
            'description' => 'Actionable second description',
        ]);
        $this->assertSame(array_map(intval(...), $postIds), $authorizedModels);
    }

    #[Test]
    #[TestWith([self::FIELD_CAN_SEE], 'field canSee')]
    #[TestWith([self::ACTION_CAN_SEE], 'action canSee')]
    #[TestWith([self::ACTION_CAN_RUN], 'action canRun')]
    public function update_with_a_denied_field_action_is_forbidden_before_writing(string $denial): void
    {
        $post = Post::factory()->create([
            'title' => 'Original title',
            'description' => 'Original description',
        ]);

        PostRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Field::new('title'),
                $this->deniedDescriptionField($denial),
            ]);

        $this
            ->putJson(PostRepository::route($post), [
                'title' => 'Updated title',
                'description' => 'Updated description',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas(Post::class, [
            'id' => $post->id,
            'title' => 'Original title',
            'description' => 'Original description',
        ]);
    }

    #[Test]
    public function update_runs_an_authorized_field_action_against_the_updated_model(): void
    {
        $post = Post::factory()->create();
        $authorizedModels = [];

        PostRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Field::new('title'),
                $this->authorizedDescriptionField($authorizedModels),
            ]);

        $this
            ->putJson(PostRepository::route($post), [
                'title' => 'Updated title',
                'description' => 'Updated description',
            ])
            ->assertOk();

        $this->assertDatabaseHas(Post::class, [
            'id' => $post->id,
            'title' => 'Updated title',
            'description' => 'Actionable Updated description',
        ]);
        $this->assertSame([$post->id], $authorizedModels);
    }

    #[Test]
    #[TestWith([self::FIELD_CAN_SEE], 'field canSee')]
    #[TestWith([self::ACTION_CAN_SEE], 'action canSee')]
    #[TestWith([self::ACTION_CAN_RUN], 'action canRun')]
    public function patch_with_a_denied_field_action_is_forbidden_before_writing(string $denial): void
    {
        $post = Post::factory()->create([
            'title' => 'Original title',
            'description' => 'Original description',
        ]);

        PostRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Field::new('title'),
                $this->deniedDescriptionField($denial),
            ]);

        $this
            ->patchJson(PostRepository::route($post), [
                'title' => 'Patched title',
                'description' => 'Patched description',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas(Post::class, [
            'id' => $post->id,
            'title' => 'Original title',
            'description' => 'Original description',
        ]);
    }

    #[Test]
    public function patch_runs_an_authorized_field_action_against_the_patched_model(): void
    {
        $post = Post::factory()->create();
        $authorizedModels = [];

        PostRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Field::new('title'),
                $this->authorizedDescriptionField($authorizedModels),
            ]);

        $this
            ->patchJson(PostRepository::route($post), [
                'description' => 'Patched description',
            ])
            ->assertOk();

        $this->assertDatabaseHas(Post::class, [
            'id' => $post->id,
            'description' => 'Actionable Patched description',
        ]);
        $this->assertSame([$post->id], $authorizedModels);
    }

    #[Test]
    #[TestWith([self::FIELD_CAN_SEE], 'field canSee')]
    #[TestWith([self::ACTION_CAN_SEE], 'action canSee')]
    #[TestWith([self::ACTION_CAN_RUN], 'action canRun')]
    public function update_bulk_with_a_denied_field_action_is_forbidden_before_writing(string $denial): void
    {
        $post = Post::factory()->create([
            'title' => 'Original title',
            'description' => 'Original description',
        ]);

        PostRepository::partialMock()
            ->shouldReceive('fieldsForUpdateBulk')
            ->andReturn([
                Field::new('title'),
                $this->deniedDescriptionField($denial),
            ]);

        $this
            ->postJson(PostRepository::route('bulk/update'), [
                ['id' => $post->id, 'title' => 'Updated title', 'description' => 'Updated description'],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas(Post::class, [
            'id' => $post->id,
            'title' => 'Original title',
            'description' => 'Original description',
        ]);
    }

    #[Test]
    public function update_bulk_denied_on_a_later_row_rolls_back_the_earlier_rows(): void
    {
        $allowedPost = Post::factory()->create(['title' => 'Allowed title']);
        $deniedPost = Post::factory()->create(['title' => 'Denied title']);

        PostRepository::partialMock()
            ->shouldReceive('fieldsForUpdateBulk')
            ->andReturn([
                Field::new('title'),
                Field::new('description')->action(
                    $this->descriptionAction()->canRun(
                        fn (Request $request, ?Model $model): bool => $model?->getKey() !== $deniedPost->id
                    )
                ),
            ]);

        $this
            ->postJson(PostRepository::route('bulk/update'), [
                ['id' => $allowedPost->id, 'title' => 'Updated title', 'description' => 'first description'],
                ['id' => $deniedPost->id, 'title' => 'Updated title', 'description' => 'second description'],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas(Post::class, [
            'id' => $allowedPost->id,
            'title' => 'Allowed title',
        ]);
        $this->assertDatabaseHas(Post::class, [
            'id' => $deniedPost->id,
            'title' => 'Denied title',
        ]);
    }

    #[Test]
    public function update_bulk_runs_an_authorized_field_action_against_each_updated_model(): void
    {
        $firstPost = Post::factory()->create();
        $secondPost = Post::factory()->create();
        $authorizedModels = [];

        PostRepository::partialMock()
            ->shouldReceive('fieldsForUpdateBulk')
            ->andReturn([
                Field::new('title'),
                $this->authorizedDescriptionField($authorizedModels),
            ]);

        $this
            ->postJson(PostRepository::route('bulk/update'), [
                ['id' => $firstPost->id, 'title' => 'First title', 'description' => 'first description'],
                ['id' => $secondPost->id, 'title' => 'Second title', 'description' => 'second description'],
            ])
            ->assertOk();

        $this->assertDatabaseHas(Post::class, [
            'id' => $firstPost->id,
            'description' => 'Actionable first description',
        ]);
        $this->assertDatabaseHas(Post::class, [
            'id' => $secondPost->id,
            'description' => 'Actionable second description',
        ]);
        $this->assertSame([$firstPost->id, $secondPost->id], $authorizedModels);
    }

    #[Test]
    public function a_denied_field_action_absent_from_the_request_is_not_forbidden(): void
    {
        $post = Post::factory()->create(['description' => 'Original description']);

        PostRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Field::new('title'),
                $this->deniedDescriptionField(self::ACTION_CAN_RUN),
            ]);

        $this
            ->putJson(PostRepository::route($post), [
                'title' => 'Updated title',
            ])
            ->assertOk();

        $this->assertDatabaseHas(Post::class, [
            'id' => $post->id,
            'title' => 'Updated title',
            'description' => 'Original description',
        ]);
    }

    #[Test]
    public function a_denied_field_action_on_a_field_the_user_cannot_update_is_skipped_not_forbidden(): void
    {
        $post = Post::factory()->create(['description' => 'Original description']);

        PostRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Field::new('title'),
                $this->deniedDescriptionField(self::ACTION_CAN_RUN)->canUpdate(fn (): bool => false),
            ]);

        $this
            ->putJson(PostRepository::route($post), [
                'title' => 'Updated title',
                'description' => 'Updated description',
            ])
            ->assertOk();

        $this->assertDatabaseHas(Post::class, [
            'id' => $post->id,
            'title' => 'Updated title',
        ]);
        $this->assertDatabaseMissing(Post::class, [
            'id' => $post->id,
            'description' => 'Actionable Updated description',
        ]);
    }

    private function deniedDescriptionField(string $denial): Field
    {
        $action = $this->descriptionAction();
        $field = Field::new('description')->action($action);

        match ($denial) {
            self::FIELD_CAN_SEE => $field->canSee(fn (): bool => false),
            self::ACTION_CAN_SEE => $action->canSee(fn (): bool => false),
            self::ACTION_CAN_RUN => $action->canRun(fn (): bool => false),
        };

        return $field;
    }

    /**
     * @param  list<int|string>  $authorizedModels
     */
    private function authorizedDescriptionField(array &$authorizedModels): Field
    {
        return Field::new('description')
            ->canSee(fn (): bool => true)
            ->action(
                $this->descriptionAction()
                    ->canSee(fn (): bool => true)
                    ->canRun(function (Request $request, ?Model $model) use (&$authorizedModels): bool {
                        $authorizedModels[] = $model?->getKey();

                        return true;
                    })
            );
    }

    private function descriptionAction(): Action
    {
        return new class extends Action
        {
            public bool $showOnShow = true;

            public function handle(RestifyRequest $request, Post $post, ?int $row = null): void
            {
                $description = $row === null
                    ? $request->input('description')
                    : data_get($request->input((string) $row), 'description');

                $post->update([
                    'description' => "Actionable {$description}",
                ]);
            }
        };
    }
}
