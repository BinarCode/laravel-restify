<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RepositoryStoreBulkRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Bulk store writes a field only when the field's `canStore` allows it, as single store does.
 */
class BulkStoreFieldAuthorizationTest extends IntegrationTestCase
{
    public static int $handled = 0;

    protected function setUp(): void
    {
        parent::setUp();

        self::$handled = 0;

        $this->authenticate();
    }

    #[Test]
    public function bulk_store_does_not_write_a_field_the_user_cannot_store(): void
    {
        $this->storeBulkFields(Field::new('description')->canStore(fn (): bool => false));

        $this->postJson(PostRepository::route('bulk'), [
            ['title' => 'First', 'description' => 'First description'],
            ['title' => 'Second', 'description' => 'Second description'],
        ])->assertOk();

        $this->assertSame([['First', null], ['Second', null]], $this->storedTitlesAndDescriptions());
    }

    #[Test]
    public function bulk_store_ignores_a_denied_field_the_same_way_single_store_does(): void
    {
        $fields = fn (): array => [
            Field::new('title'),
            Field::new('description')->canStore(fn (): bool => false),
        ];

        PostRepository::partialMock()
            ->shouldReceive('fieldsForStore')
            ->andReturnUsing($fields)
            ->shouldReceive('fieldsForStoreBulk')
            ->andReturnUsing($fields);

        $this->postJson(PostRepository::route(), [
            'title' => 'Single',
            'description' => 'Single description',
        ])->assertCreated();

        $this->postJson(PostRepository::route('bulk'), [
            ['title' => 'Bulk', 'description' => 'Bulk description'],
        ])->assertOk();

        $this->assertSame([['Single', null], ['Bulk', null]], $this->storedTitlesAndDescriptions());
    }

    #[Test]
    public function bulk_store_writes_a_field_the_user_can_store_for_every_row(): void
    {
        $this->storeBulkFields(Field::new('description')->canStore(fn (): bool => true));

        $this->postJson(PostRepository::route('bulk'), [
            ['title' => 'First', 'description' => 'First description'],
            ['title' => 'Second', 'description' => 'Second description'],
            ['title' => 'Third', 'description' => 'Third description'],
        ])->assertOk();

        $this->assertSame([
            ['First', 'First description'],
            ['Second', 'Second description'],
            ['Third', 'Third description'],
        ], $this->storedTitlesAndDescriptions());
    }

    #[Test]
    public function bulk_store_asks_can_store_for_each_row_with_that_rows_input(): void
    {
        $asked = [];

        $this->storeBulkFields(Field::new('description')->canStore(function (Request $request) use (&$asked): bool {
            $this->assertInstanceOf(RepositoryStoreBulkRequest::class, $request);

            $payload = $request->payload();
            $asked[] = array_keys($payload);

            return data_get($payload, array_key_first($payload).'.title') === 'Allowed';
        }));

        $this->postJson(PostRepository::route('bulk'), [
            ['title' => 'Allowed', 'description' => 'First description'],
            ['title' => 'Denied', 'description' => 'Second description'],
            ['title' => 'Allowed', 'description' => 'Third description'],
        ])->assertOk();

        $this->assertSame([[0], [1], [2]], $asked);
        $this->assertSame([
            ['Allowed', 'First description'],
            ['Denied', null],
            ['Allowed', 'Third description'],
        ], $this->storedTitlesAndDescriptions());
    }

    #[Test]
    public function bulk_store_does_not_apply_the_default_value_of_a_field_the_user_cannot_store(): void
    {
        $this->storeBulkFields(Field::new('description')->value('Default description')->canStore(fn (): bool => false));

        $this->postJson(PostRepository::route('bulk'), [
            ['title' => 'First'],
        ])->assertOk();

        $this->assertSame([['First', null]], $this->storedTitlesAndDescriptions());
    }

    #[Test]
    public function bulk_store_skips_the_action_of_a_field_the_user_cannot_store(): void
    {
        $this->storeBulkFields(
            Field::new('description')
                ->action($this->descriptionAction())
                ->canStore(fn (): bool => false),
        );

        $this->postJson(PostRepository::route('bulk'), [
            ['title' => 'First', 'description' => 'First description'],
            ['title' => 'Second', 'description' => 'Second description'],
        ])->assertOk();

        $this->assertSame(0, self::$handled);
        $this->assertSame([['First', null], ['Second', null]], $this->storedTitlesAndDescriptions());
    }

    #[Test]
    public function bulk_store_runs_the_action_of_a_field_the_user_can_store_for_every_row(): void
    {
        $this->storeBulkFields(
            Field::new('description')
                ->action($this->descriptionAction())
                ->canStore(fn (): bool => true),
        );

        $this->postJson(PostRepository::route('bulk'), [
            ['title' => 'First', 'description' => 'First description'],
            ['title' => 'Second', 'description' => 'Second description'],
        ])->assertOk();

        $this->assertSame(2, self::$handled);
        $this->assertSame([
            ['First', 'Actionable First description'],
            ['Second', 'Actionable Second description'],
        ], $this->storedTitlesAndDescriptions());
    }

    #[Test]
    #[TestWith([true, false], 'canStore allows, canUpdateBulk denies')]
    #[TestWith([false, true], 'canStore denies, canUpdateBulk allows')]
    #[TestWith([false, false], 'both deny')]
    public function bulk_store_does_not_write_a_field_either_callback_denies(bool $canStore, bool $canUpdateBulk): void
    {
        $this->storeBulkFields(
            Field::new('description')
                ->canStore(fn (): bool => $canStore)
                ->canUpdateBulk(fn (): bool => $canUpdateBulk),
        );

        $this->postJson(PostRepository::route('bulk'), [
            ['title' => 'First', 'description' => 'First description'],
        ])->assertOk();

        $this->assertSame([['First', null]], $this->storedTitlesAndDescriptions());
    }

    #[Test]
    public function bulk_store_writes_a_field_both_callbacks_allow(): void
    {
        $this->storeBulkFields(
            Field::new('description')
                ->canStore(fn (): bool => true)
                ->canUpdateBulk(fn (): bool => true),
        );

        $this->postJson(PostRepository::route('bulk'), [
            ['title' => 'First', 'description' => 'First description'],
        ])->assertOk();

        $this->assertSame([['First', 'First description']], $this->storedTitlesAndDescriptions());
    }

    #[Test]
    public function bulk_update_still_follows_can_update_bulk_only(): void
    {
        $posts = Post::factory(2)->create(['user_id' => 1, 'description' => 'Original description', 'category' => 'original']);

        PostRepository::partialMock()
            ->shouldReceive('fieldsForUpdateBulk')
            ->andReturn([
                Field::new('title'),
                Field::new('description')->canStore(fn (): bool => false)->canUpdate(fn (): bool => false),
                Field::new('category')->canUpdateBulk(fn (): bool => false),
            ]);

        $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => $posts[0]->id, 'title' => 'First', 'description' => 'First description', 'category' => 'changed'],
            ['id' => $posts[1]->id, 'title' => 'Second', 'description' => 'Second description', 'category' => 'changed'],
        ])->assertOk();

        $this->assertSame([
            ['First', 'First description'],
            ['Second', 'Second description'],
        ], $this->storedTitlesAndDescriptions());
        $this->assertSame(['original', 'original'], Post::query()->orderBy('id')->pluck('category')->all());
    }

    private function storeBulkFields(Field $description): void
    {
        PostRepository::partialMock()
            ->shouldReceive('fieldsForStoreBulk')
            ->andReturn([
                Field::new('title'),
                $description,
            ]);
    }

    /**
     * @return list<array{string, string|null}>
     */
    private function storedTitlesAndDescriptions(): array
    {
        return Post::query()
            ->orderBy('id')
            ->get()
            ->map(fn (Post $post): array => [$post->title, $post->description])
            ->all();
    }

    private function descriptionAction(): Action
    {
        return new class extends Action
        {
            public function handle(RestifyRequest $request, Post $post, ?int $row = null): void
            {
                BulkStoreFieldAuthorizationTest::$handled++;

                $post->update([
                    'description' => 'Actionable '.data_get($request->input((string) $row), 'description'),
                ]);
            }
        };
    }
}
