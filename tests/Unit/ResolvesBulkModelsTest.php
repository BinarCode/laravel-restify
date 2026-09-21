<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Http\Controllers\Concerns\ResolvesBulkModels;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class ResolvesBulkModelsTest extends IntegrationTestCase
{
    #[Test]
    public function an_integer_key_the_database_coerced_still_resolves(): void
    {
        $post = (new Post)->forceFill(['id' => 1]);

        $resolved = $this->resolve(new Post, [$post], ['01']);

        $this->assertSame(1, $resolved[0]->getKey());
    }

    #[Test]
    public function a_string_route_key_is_matched_exactly(): void
    {
        $post = (new SluggedPost)->forceFill(['id' => 1, 'title' => '123']);

        $resolved = $this->resolve(new SluggedPost, [$post], ['123']);

        $this->assertSame('123', $resolved[0]->title);
    }

    #[Test]
    public function a_string_route_key_is_not_matched_numerically(): void
    {
        $post = (new SluggedPost)->forceFill(['id' => 1, 'title' => '123']);

        $this->expectException(ModelNotFoundException::class);

        $this->resolve(new SluggedPost, [$post], ['0123']);
    }

    /**
     * @param  list<Model>  $loaded
     * @param  list<int|string>  $keys
     * @return array<int|string, Model>
     */
    private function resolve(Model $model, array $loaded, array $keys): array
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('lockForUpdate')->andReturnSelf();
        $query->shouldReceive('get')->andReturn(Collection::make($loaded));

        $request = Mockery::mock(RestifyRequest::class);
        $request->shouldReceive('model')->andReturn($model);
        $request->shouldReceive('modelsQuery')->andReturn($query);

        $controller = new class
        {
            use ResolvesBulkModels;

            /**
             * @param  list<int|string>  $keys
             * @return array<int|string, Model>
             */
            public function resolve(RestifyRequest $request, array $keys): array
            {
                return $this->resolveBulkModels($request, $keys);
            }
        };

        return $controller->resolve($request, $keys);
    }
}

class SluggedPost extends Post
{
    protected $table = 'posts';

    public function getRouteKeyName(): string
    {
        return 'title';
    }
}
