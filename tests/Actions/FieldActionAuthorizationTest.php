<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Actions;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Exceptions\UnauthorizedException;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RepositoryUpdateBulkRequest;
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
    private const string ACTION_CAN_SEE = 'action-can-see';

    private const string ACTION_CAN_RUN = 'action-can-run';

    public static int $handled = 0;

    protected function setUp(): void
    {
        parent::setUp();

        self::$handled = 0;
    }

    protected function tearDown(): void
    {
        self::$handled = 0;

        parent::tearDown();
    }

    #[Test]
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
        $this->assertSame([(int) $postId => 'Title'], $authorizedModels);
    }

    #[Test]
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
        $this->assertSame(
            [(int) $postIds[0] => 'First title', (int) $postIds[1] => 'Second title'],
            $authorizedModels
        );
    }

    #[Test]
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
        $post = Post::factory()->create(['title' => 'Original title']);
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
        $this->assertSame([$post->id => 'Original title'], $authorizedModels);
    }

    #[Test]
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
        $post = Post::factory()->create(['title' => 'Original title']);
        $authorizedModels = [];

        PostRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Field::new('title'),
                $this->authorizedDescriptionField($authorizedModels),
            ]);

        $this
            ->patchJson(PostRepository::route($post), [
                'title' => 'Patched title',
                'description' => 'Patched description',
            ])
            ->assertOk();

        $this->assertDatabaseHas(Post::class, [
            'id' => $post->id,
            'title' => 'Patched title',
            'description' => 'Actionable Patched description',
        ]);
        $this->assertSame([$post->id => 'Original title'], $authorizedModels);
    }

    #[Test]
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
    public function update_bulk_denied_on_a_later_row_runs_no_action_and_writes_nothing(): void
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
        $this->assertSame(0, self::$handled);
    }

    #[Test]
    public function update_bulk_runs_an_authorized_field_action_against_each_updated_model(): void
    {
        $firstPost = Post::factory()->create(['title' => 'Original first title']);
        $secondPost = Post::factory()->create(['title' => 'Original second title']);
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
        $this->assertSame(
            [$firstPost->id => 'Original first title', $secondPost->id => 'Original second title'],
            $authorizedModels
        );
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
    public function a_denied_field_action_left_out_of_a_patch_is_not_forbidden(): void
    {
        $post = Post::factory()->create(['description' => 'Original description']);

        PostRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Field::new('title'),
                $this->deniedDescriptionField(self::ACTION_CAN_RUN),
            ]);

        $this
            ->patchJson(PostRepository::route($post), [
                'title' => 'Patched title',
            ])
            ->assertOk();

        $this->assertDatabaseHas(Post::class, [
            'id' => $post->id,
            'title' => 'Patched title',
            'description' => 'Original description',
        ]);
    }

    #[Test]
    #[TestWith(['put', 'canUpdate'], 'update')]
    #[TestWith(['patch', 'canPatch'], 'patch')]
    public function a_denied_field_action_on_a_field_the_user_cannot_update_is_skipped_not_forbidden(string $method, string $gate): void
    {
        $post = Post::factory()->create(['description' => 'Original description']);

        PostRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Field::new('title'),
                $this->deniedDescriptionField(self::ACTION_CAN_RUN)->{$gate}(fn (): bool => false),
            ]);

        $this
            ->json($method, PostRepository::route($post), [
                'title' => 'Updated title',
                'description' => 'Updated description',
            ])
            ->assertOk();

        $this->assertDatabaseHas(Post::class, [
            'id' => $post->id,
            'title' => 'Updated title',
            'description' => 'Original description',
        ]);
        $this->assertSame(0, self::$handled);
    }

    #[Test]
    public function update_bulk_runs_only_the_field_actions_it_authorized(): void
    {
        $post = Post::factory()->create(['description' => 'Original description']);
        $canUpdateBulkCalls = 0;

        PostRepository::partialMock()
            ->shouldReceive('fieldsForUpdateBulk')
            ->andReturn([
                Field::new('title'),
                $this->deniedDescriptionField(self::ACTION_CAN_RUN)
                    ->canUpdateBulk(function () use (&$canUpdateBulkCalls): bool {
                        return $canUpdateBulkCalls++ > 0;
                    }),
            ]);

        $this
            ->postJson(PostRepository::route('bulk/update'), [
                ['id' => $post->id, 'title' => 'Updated title', 'description' => 'Updated description'],
            ])
            ->assertOk();

        $this->assertSame(0, self::$handled);
    }

    #[Test]
    public function store_runs_only_the_field_actions_it_authorized(): void
    {
        $canStoreCalls = 0;

        PostRepository::partialMock()
            ->shouldReceive('fieldsForStore')
            ->andReturn([
                Field::new('title'),
                $this->deniedDescriptionField(self::ACTION_CAN_SEE)
                    ->canStore(function () use (&$canStoreCalls): bool {
                        return $canStoreCalls++ > 0;
                    }),
            ]);

        $this
            ->postJson(PostRepository::route(), [
                'title' => 'Title',
                'description' => 'Description',
            ])
            ->assertCreated();

        $this->assertSame(0, self::$handled);
    }

    #[Test]
    public function update_bulk_called_directly_authorizes_the_row_itself(): void
    {
        $post = Post::factory()->create(['description' => 'Original description']);

        PostRepository::partialMock()
            ->shouldReceive('fieldsForUpdateBulk')
            ->andReturn([
                Field::new('title'),
                $this->deniedDescriptionField(self::ACTION_CAN_RUN),
            ]);

        $request = RepositoryUpdateBulkRequest::create('/', 'POST', [
            ['id' => $post->id, 'title' => 'Updated title', 'description' => 'Updated description'],
        ]);

        try {
            PostRepository::resolveWith($post)->updateBulk($request, $post->id, 0);
            $this->fail('updateBulk() ran a field action whose canRun denies.');
        } catch (UnauthorizedException) {
        }

        $this->assertDatabaseHas(Post::class, [
            'id' => $post->id,
            'title' => $post->title,
            'description' => 'Original description',
        ]);
        $this->assertSame(0, self::$handled);
    }

    #[Test]
    public function an_authorization_is_used_once_and_only_for_the_model_it_was_made_for(): void
    {
        $post = Post::factory()->create();
        $otherPost = Post::factory()->create();
        $canRunCalls = [];

        PostRepository::partialMock()
            ->shouldReceive('fieldsForUpdateBulk')
            ->andReturn([
                Field::new('description')->action(
                    $this->descriptionAction()->canRun(function (Request $request, ?Model $model) use (&$canRunCalls): bool {
                        $canRunCalls[] = $model?->getKey();

                        return true;
                    })
                ),
            ]);

        $request = RepositoryUpdateBulkRequest::create('/', 'POST', [
            ['id' => $post->id, 'description' => 'Updated description'],
        ]);

        $repository = PostRepository::resolveWith($post);
        $repository->authorizeUpdateBulkActions($request, 0);
        $repository->updateBulk($request, $post->id, 0);
        $repository->updateBulk($request, $post->id, 0);

        $repository->authorizeUpdateBulkActions($request, 0);
        $repository->withResource($otherPost)->updateBulk($request, $otherPost->id, 0);

        $this->assertSame([$post->id, $post->id, $post->id, $otherPost->id], $canRunCalls);
        $this->assertSame(3, self::$handled);
    }

    #[Test]
    public function store_calls_the_action_can_see_once(): void
    {
        $canSeeCalls = 0;
        $action = $this->descriptionAction()->canSee(function () use (&$canSeeCalls): bool {
            $canSeeCalls++;

            return true;
        });

        PostRepository::partialMock()
            ->shouldReceive('fieldsForStore')
            ->andReturn([
                Field::new('title'),
                Field::new('description')->action($action),
            ]);

        $this
            ->postJson(PostRepository::route(), [
                'title' => 'Title',
                'description' => 'Description',
            ])
            ->assertCreated();

        $this->assertSame(1, $canSeeCalls);
    }

    #[Test]
    public function store_denied_by_the_action_can_see_is_rejected_before_saving(): void
    {
        $created = 0;
        Post::created(function () use (&$created): void {
            $created++;
        });

        PostRepository::partialMock()
            ->shouldReceive('fieldsForStore')
            ->andReturn([
                Field::new('title'),
                $this->deniedDescriptionField(self::ACTION_CAN_SEE),
            ]);

        $this
            ->postJson(PostRepository::route(), [
                'title' => 'Title',
                'description' => 'Description',
            ])
            ->assertForbidden();

        $this->assertSame(0, $created);
    }

    #[Test]
    public function store_bulk_denied_by_the_action_can_see_on_a_later_row_is_rejected_before_saving(): void
    {
        $created = 0;
        Post::created(function () use (&$created): void {
            $created++;
        });

        PostRepository::partialMock()
            ->shouldReceive('fieldsForStoreBulk')
            ->andReturn([
                Field::new('title'),
                $this->deniedDescriptionField(self::ACTION_CAN_SEE),
            ]);

        $this
            ->postJson(PostRepository::route('bulk'), [
                ['title' => 'First title'],
                ['title' => 'Second title', 'description' => 'second description'],
            ])
            ->assertForbidden();

        $this->assertSame(0, $created);
        $this->assertSame(0, self::$handled);
    }

    #[Test]
    public function a_field_hidden_by_can_see_still_runs_its_authorized_action(): void
    {
        $post = Post::factory()->create(['description' => 'Original description']);

        PostRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Field::new('title'),
                Field::new('description')
                    ->canSee(fn (): bool => false)
                    ->action($this->descriptionAction()),
            ]);

        $this
            ->putJson(PostRepository::route($post), [
                'title' => 'Updated title',
                'description' => 'Updated description',
            ])
            ->assertOk();

        $this->assertDatabaseHas(Post::class, [
            'id' => $post->id,
            'description' => 'Actionable Updated description',
        ]);
    }

    #[Test]
    public function update_bulk_calls_can_run_once_per_row(): void
    {
        $firstPost = Post::factory()->create();
        $secondPost = Post::factory()->create();
        $canRunCalls = [];

        PostRepository::partialMock()
            ->shouldReceive('fieldsForUpdateBulk')
            ->andReturn([
                Field::new('title'),
                Field::new('description')->action(
                    $this->descriptionAction()->canRun(function (Request $request, ?Model $model) use (&$canRunCalls): bool {
                        $canRunCalls[] = $model?->getKey();

                        return true;
                    })
                ),
            ]);

        $this
            ->postJson(PostRepository::route('bulk/update'), [
                ['id' => $firstPost->id, 'description' => 'first description'],
                ['id' => $secondPost->id, 'description' => 'second description'],
            ])
            ->assertOk();

        $this->assertSame([$firstPost->id, $secondPost->id], $canRunCalls);
        $this->assertSame(2, self::$handled);
    }

    private function deniedDescriptionField(string $denial): Field
    {
        $action = $this->descriptionAction();
        $field = Field::new('description')->action($action);

        match ($denial) {
            self::ACTION_CAN_SEE => $action->canSee(fn (): bool => false),
            self::ACTION_CAN_RUN => $action->canRun(fn (): bool => false),
        };

        return $field;
    }

    /**
     * @param  array<int|string, mixed>  $authorizedModels
     */
    private function authorizedDescriptionField(array &$authorizedModels): Field
    {
        return Field::new('description')
            ->action(
                $this->descriptionAction()
                    ->canSee(fn (): bool => true)
                    ->canRun(function (Request $request, ?Model $model) use (&$authorizedModels): bool {
                        $authorizedModels[$model?->getKey()] = $model?->getAttribute('title');

                        return true;
                    })
            );
    }

    private function descriptionAction(): Action
    {
        return new class extends Action
        {
            public function handle(RestifyRequest $request, Post $post, ?int $row = null): void
            {
                FieldActionAuthorizationTest::$handled++;

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
