<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature\Filters;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\DB;

class SearchableCaseModeTest extends IntegrationTestCase
{
    protected function tearDown(): void
    {
        config([
            'restify.search.case_sensitive' => false,
            'restify.search.use_joins_for_belongs_to' => false,
        ]);

        PostRepository::$search = ['id', 'title'];
        PostRepository::$related = [];

        parent::tearDown();
    }

    public function test_default_case_insensitive_emits_upper_both(): void
    {
        config(['restify.search.case_sensitive' => false]);

        PostRepository::$search = ['title'];

        Post::factory()->create(['title' => 'Hello World']);

        DB::enableQueryLog();

        $this->getJson(PostRepository::route(query: ['search' => 'hello']))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $sql = $this->lastSearchQuery();

        $this->assertStringContainsStringIgnoringCase('upper(', $sql);
        $this->assertStringContainsStringIgnoringCase("like '%HELLO%'", $sql);
    }

    public function test_default_case_sensitive_emits_raw(): void
    {
        config(['restify.search.case_sensitive' => true]);

        PostRepository::$search = ['title'];

        Post::factory()->create(['title' => 'Hello World']);

        DB::enableQueryLog();

        $this->getJson(PostRepository::route(query: ['search' => 'Hello']))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $sql = $this->lastSearchQuery();

        $this->assertStringNotContainsStringIgnoringCase('upper(', $sql);
        $this->assertStringNotContainsStringIgnoringCase('lower(', $sql);
        $this->assertStringContainsStringIgnoringCase("like '%Hello%'", $sql);
    }

    public function test_case_raw_per_field_overrides_global_insensitive(): void
    {
        config(['restify.search.case_sensitive' => false]);

        PostRepository::$search = [
            SearchableFilter::make()->setColumn('title')->caseRaw(),
        ];

        Post::factory()->create(['title' => 'Hello World']);

        DB::enableQueryLog();

        $this->getJson(PostRepository::route(query: ['search' => 'Hello']))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $sql = $this->lastSearchQuery();

        $this->assertStringNotContainsStringIgnoringCase('upper(', $sql);
        $this->assertStringContainsStringIgnoringCase("like '%Hello%'", $sql);
    }

    public function test_upper_value_keeps_column_raw_and_uppercases_value(): void
    {
        config(['restify.search.case_sensitive' => true]);

        PostRepository::$search = [
            SearchableFilter::make()->setColumn('title')->upperValue(),
        ];

        Post::factory()->create(['title' => 'ACME01']);

        DB::enableQueryLog();

        $this->getJson(PostRepository::route(query: ['search' => 'acme']))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $sql = $this->lastSearchQuery();

        $this->assertStringNotContainsStringIgnoringCase('upper(', $sql);
        $this->assertStringContainsStringIgnoringCase("like '%ACME%'", $sql);
    }

    public function test_lower_value_keeps_column_raw_and_lowercases_value(): void
    {
        config(['restify.search.case_sensitive' => true]);

        PostRepository::$search = [
            SearchableFilter::make()->setColumn('title')->lowerValue(),
        ];

        Post::factory()->create(['title' => 'posted']);

        DB::enableQueryLog();

        $this->getJson(PostRepository::route(query: ['search' => 'POSTED']))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $sql = $this->lastSearchQuery();

        $this->assertStringNotContainsStringIgnoringCase('lower(', $sql);
        $this->assertStringContainsStringIgnoringCase("like '%posted%'", $sql);
    }

    public function test_lower_both_emits_lower_on_column_and_value(): void
    {
        config(['restify.search.case_sensitive' => true]);

        PostRepository::$search = [
            SearchableFilter::make()->setColumn('title')->lowerBoth(),
        ];

        Post::factory()->create(['title' => 'Hello']);

        DB::enableQueryLog();

        $this->getJson(PostRepository::route(query: ['search' => 'HELLO']))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $sql = $this->lastSearchQuery();

        $this->assertStringContainsStringIgnoringCase('lower(', $sql);
        $this->assertStringContainsStringIgnoringCase("like '%hello%'", $sql);
    }

    public function test_transform_with_custom_closure_applies_to_value(): void
    {
        config(['restify.search.case_sensitive' => true]);

        PostRepository::$search = [
            SearchableFilter::make()
                ->setColumn('title')
                ->transform(static fn (string $value): string => trim(strtolower($value))),
        ];

        Post::factory()->create(['title' => 'foo']);

        DB::enableQueryLog();

        $this->getJson(PostRepository::route(query: ['search' => '  FOO  ']))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $sql = $this->lastSearchQuery();

        $this->assertStringNotContainsStringIgnoringCase('upper(', $sql);
        $this->assertStringNotContainsStringIgnoringCase('lower(', $sql);
        $this->assertStringContainsStringIgnoringCase("like '%foo%'", $sql);
    }

    public function test_belongs_to_searchable_mixes_strings_and_searchable_filter_instances(): void
    {
        config([
            'restify.search.case_sensitive' => false,
            'restify.search.use_joins_for_belongs_to' => true,
        ]);

        PostRepository::$search = [];
        PostRepository::$related = [
            'user' => BelongsTo::make('user', UserRepository::class)->searchable([
                SearchableFilter::make()->setColumn('users.email')->upperValue(),
                'users.name',
            ]),
        ];

        $john = User::factory()->create(['name' => 'John', 'email' => 'JOHN@EXAMPLE.COM']);
        Post::factory(2)->create(['user_id' => $john->id]);

        DB::enableQueryLog();

        $this->getJson(PostRepository::route(query: ['search' => 'john']))
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $sql = $this->lastSearchQuery();

        $this->assertStringContainsStringIgnoringCase('upper(users.name)', $sql);
        $this->assertStringContainsStringIgnoringCase("\"users\".\"email\" like '%JOHN%'", $sql);
    }

    private function lastSearchQuery(): string
    {
        $log = collect(DB::getQueryLog())
            ->reverse()
            ->first(static fn (array $entry): bool => str_contains(strtolower($entry['query']), ' like ')
                || str_contains(strtolower($entry['query']), ' ilike '));

        $this->assertNotNull(
            $log,
            'no LIKE/ILIKE query captured. all: '.json_encode(
                collect(DB::getQueryLog())->pluck('query')->all(),
                JSON_PRETTY_PRINT,
            ),
        );

        $sql = $log['query'];

        foreach ($log['bindings'] as $binding) {
            $sql = preg_replace(
                '/\?/',
                is_string($binding) ? "'".addslashes($binding)."'" : (string) $binding,
                $sql,
                1,
            );
        }

        return $sql;
    }
}
