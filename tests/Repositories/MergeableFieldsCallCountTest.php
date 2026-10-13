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

    #[Test]
    public function it_calls_fields_exactly_once_per_extra_row_on_a_mergeable_index(): void
    {
        Restify::repositories([
            MergeableFieldsCallCountRepository::class,
        ]);

        PostFactory::one();

        $this->getJson(MergeableFieldsCallCountRepository::route())->assertOk();

        $callsForOneRow = MergeableFieldsCallCountRepository::$fieldsCalls;

        MergeableFieldsCallCountRepository::$fieldsCalls = 0;

        Post::query()->delete();

        PostFactory::many(4);

        $this->getJson(MergeableFieldsCallCountRepository::route())->assertOk();

        $callsForFourRows = MergeableFieldsCallCountRepository::$fieldsCalls;

        // Going from 1 to 4 rows must add exactly 3 extra fields() calls (one per extra
        // row) - not one per model attribute (Post has ~10 columns), which would add
        // roughly 3 * 10 = 30 extra calls on unfixed 10.x code.
        $this->assertSame(3, $callsForFourRows - $callsForOneRow);
    }

    #[Test]
    public function it_calls_fields_no_more_often_on_a_mergeable_show_than_on_a_non_mergeable_one(): void
    {
        Restify::repositories([
            MergeableFieldsCallCountRepository::class,
            NonMergeableFieldsCallCountRepository::class,
        ]);

        $post = PostFactory::one();

        $this->getJson(NonMergeableFieldsCallCountRepository::route($post))->assertOk();
        $baselineCalls = NonMergeableFieldsCallCountRepository::$fieldsCalls;

        $this->getJson(MergeableFieldsCallCountRepository::route($post))->assertOk();
        $mergeableCalls = MergeableFieldsCallCountRepository::$fieldsCalls;

        $this->assertSame(0, $mergeableCalls - $baselineCalls);
    }
}

class MergeableFieldsCallCountRepository extends Repository implements Mergeable
{
    public static $model = Post::class;

    public static int $fieldsCalls = 0;

    public function fields(RestifyRequest $request): array
    {
        self::$fieldsCalls++;

        return [
            Field::new('title'),
        ];
    }
}

class NonMergeableFieldsCallCountRepository extends Repository
{
    public static $model = Post::class;

    public static int $fieldsCalls = 0;

    public function fields(RestifyRequest $request): array
    {
        self::$fieldsCalls++;

        return [
            Field::new('title'),
        ];
    }
}
