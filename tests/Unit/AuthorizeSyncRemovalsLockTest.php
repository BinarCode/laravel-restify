<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as EloquentBelongsToMany;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;

class AuthorizeSyncRemovalsLockTest extends IntegrationTestCase
{
    #[Test]
    public function it_locks_the_removed_pivots_query_for_update(): void
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('whereNotIn')->once()->with('user_id', [1, 2])->andReturnSelf();
        $query->shouldReceive('lockForUpdate')->once()->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn(Collection::make());

        $relationship = Mockery::mock(EloquentBelongsToMany::class);
        $relationship->shouldReceive('newPivotQuery')->once()->andReturn($query);

        $eagerField = Mockery::mock(BelongsToMany::class);

        $repository = new CompanyRepository;

        $method = new ReflectionMethod($repository, 'authorizeSyncRemovals');
        $method->invoke($repository, new RestifyRequest, $eagerField, $relationship, 'user_id', [1, 2]);
    }
}
