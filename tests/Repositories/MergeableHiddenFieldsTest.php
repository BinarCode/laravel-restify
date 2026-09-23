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
use PHPUnit\Framework\Attributes\Test;

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
