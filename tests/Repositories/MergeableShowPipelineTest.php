<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Mergeable;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

class MergeableShowPipelineTest extends IntegrationTestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_the_model_attributes_filtered_by_field_visibility_on_a_mergeable_show(): void
    {
        Restify::repositories([MergeableShowPipelineRepository::class]);

        $post = $this->createPost();

        $this->getJson(MergeableShowPipelineRepository::route($post))
            ->assertOk()
            ->assertJsonPath('data.id', (string) $post->id)
            ->assertJsonPath('data.attributes', Arr::except($post->fresh()->toArray(), ['description', 'category']));
    }

    #[Test]
    public function it_does_not_run_the_field_pipeline_on_a_mergeable_show(): void
    {
        Restify::repositories([MergeableShowPipelineRepository::class]);

        $this->getJson(MergeableShowPipelineRepository::route($this->createPost()))->assertOk();

        $this->assertSame(0, MergeableShowPipelineRepository::$resolved);
    }

    #[Test]
    public function it_still_runs_the_field_pipeline_on_a_non_mergeable_show(): void
    {
        Restify::repositories([NonMergeableShowPipelineRepository::class]);

        $this->getJson(NonMergeableShowPipelineRepository::route($this->createPost()))
            ->assertOk()
            ->assertJsonPath('data.attributes', [
                'title' => 'Raw title',
                'image' => 'resolved',
            ]);

        $this->assertSame(1, NonMergeableShowPipelineRepository::$resolved);
    }

    private function createPost(): Post
    {
        return Post::query()->create([
            'user_id' => 1,
            'title' => 'Raw title',
            'description' => 'Raw description',
            'category' => 'Raw category',
            'image' => 'raw.png',
        ]);
    }
}

class MergeableShowPipelineRepository extends Repository implements Mergeable
{
    public static $model = Post::class;

    public static int $resolved = 0;

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),
            field('description')->hidden(),
            field('category')->canSee(fn () => false),
            field('image')->resolveCallback(function (): string {
                self::$resolved++;

                return 'resolved';
            }),
        ];
    }
}

class NonMergeableShowPipelineRepository extends Repository
{
    public static $model = Post::class;

    public static int $resolved = 0;

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),
            field('description')->hidden(),
            field('category')->canSee(fn () => false),
            field('image')->resolveCallback(function (): string {
                self::$resolved++;

                return 'resolved';
            }),
        ];
    }
}
