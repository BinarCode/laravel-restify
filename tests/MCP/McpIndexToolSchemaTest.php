<?php

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;

class McpIndexToolSchemaTest extends IntegrationTestCase
{
    protected function tearDown(): void
    {
        Restify::$repositories = [];

        parent::tearDown();
    }

    protected function repositoryWith(array $search = [], array $sort = [], array $match = []): Repository
    {
        return new class($search, $sort, $match) extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static array $search = [];

            public static array $sort = [];

            public static array $match = [];

            public function __construct(array $search = [], array $sort = [], array $match = [])
            {
                self::$search = $search;
                self::$sort = $sort;
                self::$match = $match;
            }
        };
    }

    protected function schema(Repository $repository): array
    {
        return $repository::indexToolSchema(new JsonSchemaTypeFactory);
    }

    public function test_search_property_lists_searchable_fields(): void
    {
        $schema = $this->schema($this->repositoryWith(search: ['id', 'title']));

        $this->assertArrayHasKey('search', $schema);

        $description = $schema['search']->toArray()['description'];

        $this->assertStringContainsString('Full-text search across: id, title', $description);
        $this->assertStringNotContainsString('No searchable fields available', $description);
        $this->assertStringNotContainsString('by name or description', $description);
    }

    public function test_search_property_is_omitted_when_no_searchables(): void
    {
        $schema = $this->schema($this->repositoryWith(search: []));

        $this->assertArrayNotHasKey('search', $schema);
    }

    public function test_sort_property_lists_sort_options(): void
    {
        $schema = $this->schema($this->repositoryWith(sort: ['title', 'is_active']));

        $this->assertArrayHasKey('sort', $schema);

        $description = $schema['sort']->toArray()['description'];

        $this->assertStringContainsString('title', $description);
        $this->assertStringContainsString('is_active', $description);
        $this->assertStringContainsString("Prefix with '-' for descending", $description);
    }

    public function test_matcher_property_uses_description_without_clunky_prefix(): void
    {
        $schema = $this->schema($this->repositoryWith(match: [
            'title' => RestifySearchable::MATCH_TEXT,
        ]));

        $this->assertArrayHasKey('title', $schema);

        $description = $schema['title']->toArray()['description'];

        $this->assertStringNotContainsString('Description:', $description);
        $this->assertStringNotContainsString('resource.', $description);
        $this->assertSame(
            "Exact match on title (title=value). Prefix with '-' to negate (-title=value).",
            $description
        );
    }

    public function test_matcher_property_respects_custom_description(): void
    {
        $schema = $this->schema($this->repositoryWith(match: [
            'title' => MatchFilter::make()
                ->setType(RestifySearchable::MATCH_TEXT)
                ->setDescription('Filter posts by their title.'),
        ]));

        $this->assertSame(
            'Filter posts by their title.',
            $schema['title']->toArray()['description']
        );
    }

    public function test_default_templates_per_match_type(): void
    {
        $cases = [
            RestifySearchable::MATCH_TEXT => "Exact match on col (col=value). Prefix with '-' to negate (-col=value).",
            RestifySearchable::MATCH_INTEGER => "Exact match on col (col=value). Prefix with '-' to negate (-col=value).",
            RestifySearchable::MATCH_BOOL => "Boolean match on col (col=true or col=false). Prefix with '-' to negate (-col=true).",
            RestifySearchable::MATCH_BETWEEN => "Range match on col (col=min,max). Prefix with '-' to negate (-col=min,max).",
            RestifySearchable::MATCH_DATETIME => "Date match on col (col=YYYY-MM-DD, or col=start,end for a range). Prefix with '-' to negate (-col=YYYY-MM-DD).",
            RestifySearchable::MATCH_ARRAY => "Array match on col (col=value1,value2 matches any listed value). Prefix with '-' to negate (-col=value1,value2).",
        ];

        foreach ($cases as $type => $expected) {
            $filter = MatchFilter::make()->setColumn('col')->setType($type);

            $this->assertSame($expected, $filter->description(), "Failed default template for [{$type}].");
        }
    }

    public function test_default_templates_have_no_grammar_or_typo_bugs(): void
    {
        foreach ([
            RestifySearchable::MATCH_TEXT,
            RestifySearchable::MATCH_BETWEEN,
            RestifySearchable::MATCH_DATETIME,
            RestifySearchable::MATCH_BOOL,
            RestifySearchable::MATCH_ARRAY,
        ] as $type) {
            $description = MatchFilter::make()->setColumn('col')->setType($type)->description();

            $this->assertStringNotContainsString('a exact match', $description);
            $this->assertStringNotContainsString('acccepted', $description);
        }
    }
}
