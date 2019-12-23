<?php

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Services\Search\SearchService;
use Binaryk\LaravelRestify\Tests\Fixtures\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Mockery as MockeryAlias;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class SearchServiceTest extends IntegrationTest
{
    /**
     * @var SearchService
     */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(SearchService::class);
    }

    public function test_should_attach_with_from_extra_in_eager_load()
    {
        $this->mockUsers();
        $this->mockPosts(1);
        User::$withs = ['posts'];
        $request = MockeryAlias::mock(RestifyRequest::class);
        $builder = User::query();
        $request->shouldReceive('get')
            ->andReturn();
        /**
         * @var Builder
         */
        $query = $this->service->prepareRelations($request, $builder, ['posts']);
        $this->assertInstanceOf(Closure::class, $query->getEagerLoads()['posts']);
    }

    public function test_should_attach_with_in_eager_load()
    {
        $this->mockUsers();
        $this->mockPosts(1);
        User::$withs = ['posts'];
        $request = MockeryAlias::mock(RestifyRequest::class);
        $builder = User::query();
        $request->shouldReceive('get')
            ->andReturn('posts');
        /**
         * @var Builder
         */
        $query = $this->service->prepareRelations($request, $builder);
        $this->assertInstanceOf(Closure::class, $query->getEagerLoads()['posts']);
    }

    public function test_should_order_desc_by_field()
    {
        $this->mockUsers(5);
        User::$sort = ['id'];
        $request = MockeryAlias::mock(RestifyRequest::class);
        $builder = User::query();
        $request->shouldReceive('get')
            ->andReturn('-id');
        /**
         * @var Builder
         */
        $query = $this->service->prepareOrders($request, $builder);
        $this->assertEquals('id', $query->getQuery()->orders[0]['column']);
        $this->assertEquals('desc', $query->getQuery()->orders[0]['direction']);
    }

    public function test_should_order_asc_by_field()
    {
        $this->mockUsers(5);
        User::$sort = ['id'];
        $request = MockeryAlias::mock(RestifyRequest::class);
        $builder = User::query();
        $request->shouldReceive('get')
            ->andReturn('id');
        /**
         * @var Builder
         */
        $query = $this->service->prepareOrders($request, $builder);
        $this->assertEquals('id', $query->getQuery()->orders[0]['column']);
        $this->assertEquals('asc', $query->getQuery()->orders[0]['direction']);
    }

    public function test_should_order_asc_by_extra_passed_field()
    {
        $this->mockUsers(5);
        User::$sort = ['id'];
        $request = MockeryAlias::mock(RestifyRequest::class);
        $builder = User::query();
        $request->shouldReceive('get')
            ->andReturn();
        /**
         * @var Builder
         */
        $query = $this->service->prepareOrders($request, $builder, [
            'sort' => 'id',
        ]);
        $this->assertEquals('id', $query->getQuery()->orders[0]['column']);
        $this->assertEquals('asc', $query->getQuery()->orders[0]['direction']);
    }

    public function test_match_fields_should_add_equal_where_clause()
    {
        $this->mockUsers();
        User::$match = ['email' => 'string'];
        $request = MockeryAlias::mock(RestifyRequest::class);
        $builder = User::query();
        $request->shouldReceive('get')
            ->andReturn('eduard.lupacescu@binarcode.com');

        $request->shouldReceive('has')
            ->andReturnTrue();
        /**
         * @var Builder
         */
        $query = $this->service->prepareMatchFields($request, $builder);
        $this->assertCount(count(User::$match), $query->getQuery()->getRawBindings()['where']);
        $this->assertEquals('eduard.lupacescu@binarcode.com', $query->getQuery()->getRawBindings()['where'][0]);
        $query->get();
        $raw = $this->lastQuery();
        $this->assertEquals($raw['query'], 'select * from "users" where "users"."email" = ?');
        $this->assertEquals($raw['bindings'], ['eduard.lupacescu@binarcode.com']);
        User::reset();
    }

    public function test_match_fields_from_extra_should_add_equal_where_clause()
    {
        $this->mockUsers();
        User::$match = ['email' => 'string'];
        $request = MockeryAlias::mock(RestifyRequest::class);
        $builder = User::query();
        $request->shouldReceive('get')
            ->andReturnArg(1); //returns the default value

        $request->shouldReceive('has')
            ->andReturnFalse();
        /**
         * @var Builder
         */
        $query = $this->service->prepareMatchFields($request, $builder, [
            'match' => [
                'email' => 'eduard.lupacescu@binarcode.com',
            ],
        ]);
        $this->assertCount(count(User::$match), $query->getQuery()->getRawBindings()['where']);
        $this->assertEquals('eduard.lupacescu@binarcode.com', $query->getQuery()->getRawBindings()['where'][0]);
        $query->get();
        $raw = $this->lastQuery();
        $this->assertEquals($raw['query'], 'select * from "users" where "users"."email" = ?');
        $this->assertEquals($raw['bindings'], ['eduard.lupacescu@binarcode.com']);
        User::reset();
    }

    public function test_match_fields_should_not_add_equal_where_if_value_passed_in_query_is_empty()
    {
        $this->mockUsers();
        User::$match = ['email' => 'string'];
        $request = MockeryAlias::mock(RestifyRequest::class);
        $builder = User::query();
        $request->shouldReceive('get')
            ->andReturn('');

        $request->shouldReceive('has')
            ->andReturnTrue();
        /**
         * @var Builder
         */
        $query = $this->service->prepareMatchFields($request, $builder);
        $this->assertCount(0, $query->getQuery()->getRawBindings()['where']);
        User::reset();
    }

    public function test_should_not_call_anything_from_search_service_if_not_searchable_instance()
    {
        $service = MockeryAlias::spy(SearchService::class);
        $this->instance(SearchService::class, $service);
        $request = MockeryAlias::mock(RestifyRequest::class);
        $class = (new class extends Model {
        });
        $resolvedService = resolve(SearchService::class);
        $resolvedService->search($request, $class);
        $service->shouldHaveReceived('search');
        $service->shouldNotReceive('prepareSearchFields');
        $service->shouldNotReceive('prepareMatchFields');
        $service->shouldNotReceive('prepareRelations');
        $service->shouldNotReceive('prepareOrders');
    }

    public function test_prepare_search_should_add_where_clause()
    {
        $this->mockUsers(1);
        $request = MockeryAlias::mock(RestifyRequest::class);
        $builder = User::query();
        $request->shouldReceive('get')
            ->andReturn('some search');
        /**
         * @var Builder
         */
        $query = $this->service->prepareSearchFields($request, $builder);
        $this->assertArrayHasKey('where', $query->getQuery()->getRawBindings());
        $this->assertCount(count(User::$search), $query->getQuery()->getRawBindings()['where']);
        foreach ($query->getQuery()->getRawBindings()['where'] as $k => $queryString) {
            $this->assertEquals('%some search%', $queryString);
        }
    }
}
