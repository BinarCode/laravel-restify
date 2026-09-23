<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Mergeable;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Database\Factories\PostFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class MergeableFieldsCallCountTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['restify.mergeable_count.fields_calls'] = 0;
    }

    protected function tearDown(): void
    {
        unset($_SERVER['restify.mergeable_count.fields_calls']);

        Restify::$repositories = [];

        parent::tearDown();
    }

    #[Test]
    public function it_calls_fields_a_constant_number_of_times_per_row_on_index(): void
    {
        Restify::repositories([
            MergeableFieldsCallCountRepository::class,
        ]);

        PostFactory::many(3);

        $this->getJson(MergeableFieldsCallCountRepository::route())->assertOk();

        // 3 one-time, per-request calls (match/sort/with field collection, unrelated to
        // this fix) + 1 collectFields() call per row for a Mergeable index - not one per
        // model attribute (Post has ~10). Before the fix this was 3 + 3 rows * 10 columns = 33.
        $this->assertSame(6, $_SERVER['restify.mergeable_count.fields_calls']);
    }

    #[Test]
    public function it_calls_fields_a_constant_number_of_times_per_row_on_show(): void
    {
        Restify::repositories([
            MergeableFieldsCallCountRepository::class,
        ]);

        $post = PostFactory::one();

        $this->getJson(MergeableFieldsCallCountRepository::route($post))->assertOk();

        // 2 one-time calls (eager "with" field collection, unrelated to this fix) +
        // 2 calls from resolveShowAttributes() itself: once for the explicit fields,
        // once more for the Mergeable model-attribute merge - not one per model
        // attribute (Post has ~10). Before the fix this was 2 + 1 + 10 = 13.
        $this->assertSame(4, $_SERVER['restify.mergeable_count.fields_calls']);
    }
}

class MergeableFieldsCallCountRepository extends Repository implements Mergeable
{
    public static $model = Post::class;

    public function fields(RestifyRequest $request): array
    {
        $_SERVER['restify.mergeable_count.fields_calls']++;

        return [
            Field::new('title'),
        ];
    }
}
