<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RepositoryStoreBulkRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryUpdateBulkRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Closure;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;

class BulkRowInputTest extends IntegrationTestCase
{
    private RestifyRequest $request;

    /**
     * @var list<array{string, int, array<array-key, mixed>}>
     */
    private array $seen = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();

        $this->app->afterResolving(RepositoryStoreBulkRequest::class, function (RepositoryStoreBulkRequest $request): void {
            $this->request = $request;
        });

        $this->app->afterResolving(RepositoryUpdateBulkRequest::class, function (RepositoryUpdateBulkRequest $request): void {
            $this->request = $request;
        });
    }

    #[Test]
    #[TestWith(['postJson'], 'json body')]
    #[TestWith(['post'], 'form encoded body')]
    public function bulk_store_fills_saves_and_runs_actions_for_each_row_against_its_own_input(string $method): void
    {
        $this->recordRowInput('fieldsForStoreBulk');

        $payload = [
            ['title' => 'First', 'description' => 'first description', 'user_id' => 1],
            ['title' => 'Second', 'description' => 'second description', 'user_id' => 1],
        ];

        $this->{$method}(PostRepository::route('bulk'), $payload)->assertOk();

        $this->assertSame($this->expectedRowInput($payload), $this->seen);
        $this->assertDatabaseHas(Post::class, ['title' => 'First', 'description' => 'Actionable first description']);
        $this->assertDatabaseHas(Post::class, ['title' => 'Second', 'description' => 'Actionable second description']);
    }

    #[Test]
    #[TestWith(['postJson'], 'json body')]
    #[TestWith(['post'], 'form encoded body')]
    public function bulk_update_fills_saves_and_runs_actions_for_each_row_against_its_own_input(string $method): void
    {
        $this->recordRowInput('fieldsForUpdateBulk');

        $posts = Post::factory(2)->create(['user_id' => 1]);

        $payload = [
            ['id' => (string) $posts[0]->getKey(), 'title' => 'First', 'description' => 'first description'],
            ['id' => (string) $posts[1]->getKey(), 'title' => 'Second', 'description' => 'second description'],
        ];

        $this->{$method}(PostRepository::route('bulk/update'), $payload)->assertOk();

        $this->assertSame($this->expectedRowInput($payload), $this->seen);
        $this->assertDatabaseHas(Post::class, ['id' => $posts[0]->getKey(), 'title' => 'First', 'description' => 'Actionable first description']);
        $this->assertDatabaseHas(Post::class, ['id' => $posts[1]->getKey(), 'title' => 'Second', 'description' => 'Actionable second description']);
    }

    #[Test]
    public function bulk_store_hands_each_row_only_its_own_files(): void
    {
        $files = [];

        PostRepository::partialMock()
            ->shouldReceive('fieldsForStoreBulk')
            ->andReturn([
                Field::new('user_id'),
                Field::new('title')->fillCallback(function (RestifyRequest $request, Post $post, string $attribute, int $row) use (&$files): void {
                    $files[$row] = array_map(
                        static fn (array $input): array => array_keys($input),
                        $request->allFiles(),
                    );

                    $post->title = $request->input("{$row}.title");
                }),
            ]);

        $this->post(PostRepository::route('bulk'), [
            ['title' => 'First', 'user_id' => 1, 'image' => UploadedFile::fake()->image('first.jpg')],
            ['title' => 'Second', 'user_id' => 1, 'image' => UploadedFile::fake()->image('second.jpg')],
        ])->assertOk();

        $this->assertSame([0 => [0 => ['image']], 1 => [1 => ['image']]], $files);
        $this->assertDatabaseCount(Post::class, 2);
    }

    #[Test]
    public function bulk_store_restores_the_whole_payload_before_the_transaction_commits(): void
    {
        $payload = [
            ['title' => 'First', 'user_id' => 1],
            ['title' => 'Second', 'user_id' => 1],
            ['title' => 'Third', 'user_id' => 1],
        ];

        $committed = [];

        Event::listen(TransactionCommitted::class, function () use (&$committed): void {
            $committed[] = $this->request->payload();
        });

        $this->postJson(PostRepository::route('bulk'), $payload)->assertOk();

        $this->assertSame([$payload], $committed);
        $this->assertSame($payload, $this->request->payload());
    }

    #[Test]
    public function bulk_update_restores_the_whole_payload_before_the_transaction_commits(): void
    {
        $posts = Post::factory(3)->create(['user_id' => 1]);

        $payload = $posts->map(static fn (Post $post): array => [
            'id' => $post->getKey(),
            'title' => "Updated {$post->getKey()}",
        ])->all();

        $committed = [];

        Event::listen(TransactionCommitted::class, function () use (&$committed): void {
            $committed[] = $this->request->payload();
        });

        $this->postJson(PostRepository::route('bulk/update'), $payload)->assertOk();

        $this->assertSame([$payload], $committed);
        $this->assertSame($payload, $this->request->payload());
    }

    #[Test]
    public function bulk_store_restores_the_whole_payload_when_a_row_fails(): void
    {
        $payload = [
            ['title' => 'First', 'user_id' => 1],
            ['title' => 'Second', 'user_id' => 1],
        ];

        Event::listen('eloquent.saving: '.Post::class, static function (Post $post): void {
            if ($post->title === 'Second') {
                throw new RuntimeException('Row failed');
            }
        });

        $this->postJson(PostRepository::route('bulk'), $payload)->assertServerError();

        $this->assertSame($payload, $this->request->payload());
        $this->assertDatabaseCount(Post::class, 0);
    }

    #[Test]
    public function bulk_update_restores_the_whole_payload_when_a_row_fails(): void
    {
        $posts = Post::factory(2)->create(['user_id' => 1, 'title' => 'Original']);

        $payload = [
            ['id' => $posts[0]->getKey(), 'title' => 'First'],
            ['id' => $posts[1]->getKey(), 'title' => 'Second'],
        ];

        Event::listen('eloquent.saving: '.Post::class, static function (Post $post): void {
            if ($post->title === 'Second') {
                throw new RuntimeException('Row failed');
            }
        });

        $this->postJson(PostRepository::route('bulk/update'), $payload)->assertServerError();

        $this->assertSame($payload, $this->request->payload());
        $this->assertDatabaseMissing(Post::class, ['title' => 'First']);
    }

    #[Test]
    public function a_bulk_row_keeps_the_query_string_and_the_application_request(): void
    {
        $posts = Post::factory(2)->create(['user_id' => 1]);

        $seen = [];

        Event::listen('eloquent.saving: '.Post::class, function (Post $post) use (&$seen): void {
            $seen[$post->title] = [
                $this->request->input('foo'),
                count(request()->json()->all()),
            ];
        });

        $this->postJson(PostRepository::route('bulk', ['foo' => 'bar']), [
            ['title' => 'First', 'user_id' => 1],
            ['title' => 'Second', 'user_id' => 1],
        ])->assertOk();

        $this->postJson(PostRepository::route('bulk/update', ['foo' => 'bar']), [
            ['id' => $posts[0]->getKey(), 'title' => 'Third'],
            ['id' => $posts[1]->getKey(), 'title' => 'Fourth'],
        ])->assertOk();

        $this->assertSame([
            'First' => ['bar', 2],
            'Second' => ['bar', 2],
            'Third' => ['bar', 2],
            'Fourth' => ['bar', 2],
        ], $seen);
    }

    #[Test]
    public function bulk_validation_still_reports_errors_by_row_index(): void
    {
        $this->postJson(PostRepository::route('bulk'), [
            ['title' => 'First', 'user_id' => 1],
            ['user_id' => 1],
        ])->assertUnprocessable()->assertJsonValidationErrors(['1.title']);

        $this->assertDatabaseCount(Post::class, 0);
    }

    private function recordRowInput(string $fieldsMethod): void
    {
        $action = new class extends Action
        {
            /**
             * @var Closure(string, int, RestifyRequest): void
             */
            public static Closure $record;

            public function handle(RestifyRequest $request, Post $post, int $row): void
            {
                (self::$record)('action', $row, $request);

                $post->update(['description' => 'Actionable '.data_get($request[$row], 'description')]);
            }
        };

        $record = function (string $step, int $row, RestifyRequest $request): void {
            $this->seen[] = [$step, $row, $request->payload()];
        };

        $action::$record = $record;

        $saving = null;

        PostRepository::partialMock()
            ->shouldReceive($fieldsMethod)
            ->andReturn([
                Field::new('user_id'),
                Field::new('title')->fillCallback(function (RestifyRequest $request, Post $post, string $attribute, int $row) use ($record, &$saving): void {
                    $record('fill', $row, $request);

                    $saving = $row;

                    $post->title = $request->input("{$row}.title");
                }),
                Field::new('description')->action($action),
            ]);

        Event::listen('eloquent.saving: '.Post::class, function () use ($record, &$saving): void {
            if ($saving !== null) {
                $record('save', $saving, $this->request);

                $saving = null;
            }
        });
    }

    /**
     * @param  list<array<string, mixed>>  $payload
     * @return list<array{string, int, array<array-key, mixed>}>
     */
    private function expectedRowInput(array $payload): array
    {
        $expected = [];

        foreach ($payload as $row => $input) {
            $expected[] = ['fill', $row, [$row => $input]];
            $expected[] = ['save', $row, [$row => $input]];
            $expected[] = ['action', $row, [$row => $input]];
        }

        return $expected;
    }
}
