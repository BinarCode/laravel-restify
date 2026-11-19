<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature\Filters;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Http\Requests\RepositoryApplyFiltersRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryIndexRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\ValueFilter;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;

/**
 * `POST /{repository}/apply-restify-advanced-filters` lists the repository, so it is an index request.
 */
class ApplyAdvancedFiltersIndexTest extends IntegrationTestCase
{
    #[Test]
    public function it_is_an_index_request_that_reads_filters_from_the_body(): void
    {
        $filters = [['key' => ValueFilter::uriKey(), 'value' => 'Listed%']];

        $request = RepositoryApplyFiltersRequest::create('/', 'POST', ['filters' => $filters]);

        $this->assertInstanceOf(RepositoryIndexRequest::class, $request);
        $this->assertTrue($request->isIndexRequest());
        $this->assertFalse($request->isShowRequest());
        $this->assertSame($filters, $request->filters());
    }

    #[Test]
    public function it_lists_only_the_rows_the_index_query_allows(): void
    {
        [$listed, $unlisted] = $this->listedAndUnlistedPost();

        $index = $this->getJson($this->repository()::route())->assertOk();
        $filtered = $this->applyFilters([])->assertOk();

        foreach ([$index, $filtered] as $response) {
            $response->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', (string) $listed->getKey())
                ->assertJsonPath('meta.total', 1)
                ->assertJsonPath('meta.last_page', 1);

            $this->assertNotContains((string) $unlisted->getKey(), $response->json('data.*.id'));
        }
    }

    #[Test]
    public function it_lists_only_the_rows_the_index_query_allows_when_no_filters_are_sent(): void
    {
        [$listed] = $this->listedAndUnlistedPost();

        $this->postJson($this->repository()::route('apply-restify-advanced-filters'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', (string) $listed->getKey())
            ->assertJsonPath('meta.total', 1);
    }

    #[Test]
    public function a_filter_cannot_match_a_row_the_index_query_excludes(): void
    {
        $this->listedAndUnlistedPost();

        $this->applyFilters([$this->titleFilter('Unlisted%')])
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    #[Test]
    public function filters_sent_in_the_body_still_narrow_the_listed_rows(): void
    {
        [$listed] = $this->listedAndUnlistedPost();
        $other = Post::factory()->create(['title' => 'Another post']);

        $this->applyFilters([])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        $this->applyFilters([$this->titleFilter('Another%')])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', (string) $other->getKey())
            ->assertJsonPath('meta.total', 1);

        $this->applyFilters([$this->titleFilter('Listed%')])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', (string) $listed->getKey());
    }

    #[Test]
    public function it_serializes_the_index_fields(): void
    {
        $this->listedAndUnlistedPost();

        $index = $this->getJson($this->repository()::route())->assertOk();
        $filtered = $this->applyFilters([])->assertOk();

        foreach ([$index, $filtered] as $response) {
            $this->assertSame(['title'], array_keys($response->json('data.0.attributes')));
        }
    }

    #[Test]
    public function it_leaves_out_a_relationship_hidden_from_the_index(): void
    {
        tap(Company::factory()->create(), function (Company $company): void {
            $company->users()->attach(User::factory()->create());
        });

        CompanyRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                'users' => BelongsToMany::make('users', UserRepository::class)->hideFromIndex(),
            ]);

        $this->postJson(CompanyRepository::route('apply-restify-advanced-filters', query: ['related' => 'users']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissingPath('data.0.relationships.users');
    }

    /**
     * @param  list<array<string, mixed>>  $filters
     */
    private function applyFilters(array $filters): TestResponse
    {
        return $this->postJson($this->repository()::route('apply-restify-advanced-filters'), [
            'filters' => $filters,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function titleFilter(string $pattern): array
    {
        return [
            'key' => ValueFilter::uriKey(),
            'value' => $pattern,
            'operator' => 'like',
            'column' => 'title',
        ];
    }

    /**
     * @return array{Post, Post}
     */
    private function listedAndUnlistedPost(): array
    {
        $listed = Post::factory()->create(['title' => 'Listed post', 'description' => 'Listed description']);
        $unlisted = Post::factory()->create(['title' => 'Unlisted post', 'description' => 'Unlisted description']);

        $this->repository()::$unlistedId = $unlisted->getKey();

        return [$listed, $unlisted];
    }

    /**
     * A posts repository whose indexQuery excludes one row, with a narrower field set for the index.
     *
     * @return class-string<Repository>
     */
    private function repository(): string
    {
        $repository = new class extends Repository
        {
            public static string $model = Post::class;

            public static string $uriKey = 'listed-posts';

            public static ?int $unlistedId = null;

            /**
             * @param  Builder<Post>|Relation<Post, Post, mixed>  $query
             * @return Builder<Post>|Relation<Post, Post, mixed>
             */
            public static function indexQuery(RestifyRequest $request, Builder|Relation $query)
            {
                return $query->whereKeyNot(self::$unlistedId);
            }

            public function fields(RestifyRequest $request): array
            {
                return [
                    field('title'),
                    field('description'),
                ];
            }

            public function fieldsForIndex(RestifyRequest $request): array
            {
                return [
                    field('title'),
                ];
            }

            public function filters(RestifyRequest $request): array
            {
                return [
                    ValueFilter::new(),
                ];
            }
        };

        Restify::repositories([$repository::class]);

        return $repository::class;
    }
}
