<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Mergeable;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Database\Factories\PostFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class MergeableHiddenFieldsTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Restify::$repositories = [];

        parent::tearDown();
    }

    #[Test]
    public function it_excludes_hidden_and_unauthorized_fields_on_index_for_a_mergeable_repository(): void
    {
        Restify::repositories([
            MergeableHiddenFieldsRepository::class,
        ]);

        PostFactory::one();

        $response = $this->getJson(MergeableHiddenFieldsRepository::route())->assertOk();

        $attributes = $response->json('data.0.attributes');

        $this->assertArrayHasKey('title', $attributes);
        $this->assertArrayHasKey('image', $attributes);
        $this->assertArrayNotHasKey('description', $attributes);
        $this->assertArrayNotHasKey('category', $attributes);
    }

    #[Test]
    public function it_excludes_hidden_and_unauthorized_fields_on_show_for_a_mergeable_repository(): void
    {
        Restify::repositories([
            MergeableHiddenFieldsRepository::class,
        ]);

        $post = PostFactory::one();

        $response = $this->getJson(MergeableHiddenFieldsRepository::route($post))->assertOk();

        $attributes = $response->json('data.attributes');

        $this->assertArrayHasKey('title', $attributes);
        $this->assertArrayHasKey('image', $attributes);
        $this->assertArrayNotHasKey('description', $attributes);
        $this->assertArrayNotHasKey('category', $attributes);
    }

    #[Test]
    #[TestWith(['visible_first', true], 'the visible field is declared first')]
    #[TestWith(['hidden_first', false], 'the hidden field is declared first')]
    public function it_keeps_the_first_duplicate_fields_visibility_on_index(string $order, bool $expectVisible): void
    {
        MergeableDuplicateFieldRepository::$order = $order;

        Restify::repositories([
            MergeableDuplicateFieldRepository::class,
        ]);

        PostFactory::one();

        $attributes = $this->getJson(MergeableDuplicateFieldRepository::route())
            ->assertOk()
            ->json('data.0.attributes');

        if ($expectVisible) {
            $this->assertArrayHasKey('title', $attributes);
        } else {
            $this->assertArrayNotHasKey('title', $attributes);
        }
    }

    #[Test]
    #[TestWith(['visible_first', true], 'the visible field is declared first')]
    #[TestWith(['hidden_first', false], 'the hidden field is declared first')]
    public function it_keeps_the_first_duplicate_fields_visibility_on_show(string $order, bool $expectVisible): void
    {
        MergeableDuplicateFieldRepository::$order = $order;

        Restify::repositories([
            MergeableDuplicateFieldRepository::class,
        ]);

        $post = PostFactory::one();

        $attributes = $this->getJson(MergeableDuplicateFieldRepository::route($post))
            ->assertOk()
            ->json('data.attributes');

        if ($expectVisible) {
            $this->assertArrayHasKey('title', $attributes);
        } else {
            $this->assertArrayNotHasKey('title', $attributes);
        }
    }

    #[Test]
    public function it_resolves_row_dependent_field_visibility_per_row_on_index(): void
    {
        Restify::repositories([
            MergeableRowVisibilityRepository::class,
        ]);

        $posts = PostFactory::many(2);

        MergeableRowVisibilityRepository::$visibleForId = $posts->first()->id;

        $data = $this->getJson(MergeableRowVisibilityRepository::route())
            ->assertOk()
            ->json('data');

        $byId = Collection::make($data)->keyBy('id');

        $this->assertArrayHasKey('title', $byId->get($posts->first()->id)['attributes']);
        $this->assertArrayNotHasKey('title', $byId->get($posts->last()->id)['attributes']);
    }

    #[Test]
    public function it_resolves_row_dependent_field_visibility_per_row_on_show(): void
    {
        Restify::repositories([
            MergeableRowVisibilityRepository::class,
        ]);

        $posts = PostFactory::many(2);

        MergeableRowVisibilityRepository::$visibleForId = $posts->first()->id;

        $matchingAttributes = $this->getJson(MergeableRowVisibilityRepository::route($posts->first()))
            ->assertOk()
            ->json('data.attributes');

        $otherAttributes = $this->getJson(MergeableRowVisibilityRepository::route($posts->last()))
            ->assertOk()
            ->json('data.attributes');

        $this->assertArrayHasKey('title', $matchingAttributes);
        $this->assertArrayNotHasKey('title', $otherAttributes);
    }
}

class MergeableHiddenFieldsRepository extends Repository implements Mergeable
{
    public static $model = Post::class;

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),
            field('description')->hidden(),
            field('category')->canSee(fn () => false),
        ];
    }
}

class MergeableDuplicateFieldRepository extends Repository implements Mergeable
{
    public static $model = Post::class;

    public static string $order = 'visible_first';

    public function fields(RestifyRequest $request): array
    {
        return match (self::$order) {
            'visible_first' => [
                field('title'),
                field('title')->canSee(fn () => false),
            ],
            'hidden_first' => [
                field('title')->canSee(fn () => false),
                field('title'),
            ],
        };
    }
}

class MergeableRowVisibilityRepository extends Repository implements Mergeable
{
    public static $model = Post::class;

    public static int $visibleForId = 0;

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title')
                ->showOnIndex(fn ($request, $repository) => $repository->resource->id === self::$visibleForId)
                ->showOnShow(fn ($request, $repository) => $repository->resource->id === self::$visibleForId),
        ];
    }
}
