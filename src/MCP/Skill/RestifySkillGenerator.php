<?php

namespace Binaryk\LaravelRestify\MCP\Skill;

use Binaryk\LaravelRestify\MCP\McpTools;
use Binaryk\LaravelRestify\Restify;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Generate an Agent Skill (SKILL.md) and an OpenAPI 3.1 description straight
 * from the live MCP-enabled repositories, so the emitted documentation never
 * drifts from what the HTTP API actually exposes.
 */
class RestifySkillGenerator
{
    private string $base;

    public function __construct()
    {
        $this->base = '/'.trim((string) config('restify.base', '/api/restify'), '/');
    }

    /**
     * @return Collection<int, array>
     */
    public function repositories(): Collection
    {
        return McpTools::getAvailableRepositories();
    }

    public function markdown(): string
    {
        $sections = $this->repositories()
            ->map(fn (array $repository): string => $this->renderRepositorySection($repository['name']))
            ->filter()
            ->implode("\n\n---\n\n");

        return $this->frontMatter()
            ."\n\n".$this->conventions()
            ."\n\n## Repositories\n\n".$sections."\n";
    }

    public function openApi(): array
    {
        $paths = [];

        foreach ($this->repositories() as $repository) {
            $paths = array_merge($paths, $this->repositoryPaths($repository['name']));
        }

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => config('app.name', 'Laravel').' Restify API',
                'version' => '1.0.0',
                'description' => 'Auto-generated from the MCP-enabled Restify repositories.',
            ],
            'servers' => [
                ['url' => $this->base],
            ],
            'security' => [
                ['bearerAuth' => []],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'description' => 'Laravel Sanctum personal access token.',
                    ],
                ],
            ],
            'paths' => $paths,
        ];
    }

    private function frontMatter(): string
    {
        $name = Str::slug(config('app.name', 'Laravel').'-restify-api');

        return implode("\n", [
            '---',
            "name: {$name}",
            'description: Query and mutate this application\'s data through the Laravel Restify HTTP API. Use for listing, filtering, sorting, showing, creating, updating and deleting the exposed resources.',
            '---',
        ]);
    }

    private function conventions(): string
    {
        return <<<MD
        # Laravel Restify API

        A REST API exposed by [Laravel Restify](https://restify.binarcode.com). Every resource lives under a predictable URL and shares the same query conventions.

        ## Base URL

        All endpoints are served under `{$this->base}/{repository}` where `{repository}` is one of the resources documented below.

        ## Authentication

        Requests are authenticated with a Laravel Sanctum bearer token. Send it in the `Authorization` header:

        ```bash
        curl -H "Authorization: Bearer \$RESTIFY_TOKEN" \\
             -H "Accept: application/json" \\
             {$this->base}/{repository}
        ```

        Store the token in an environment variable (e.g. `RESTIFY_TOKEN`) rather than hardcoding it.

        ## Pagination

        Index endpoints are paginated. Control them with query params:

        - `page` — the page number (default `1`).
        - `perPage` — items per page.

        ```bash
        {$this->base}/{repository}?page=2&perPage=25
        ```

        ## Sorting

        Use the `sort` param with a comma-separated list of columns. Prefix a column with `-` for descending order:

        ```bash
        {$this->base}/{repository}?sort=-created_at,title
        ```

        ## Related resources

        Eager load relationships with the `related` param (comma-separated):

        ```bash
        {$this->base}/{repository}?related=user,comments
        ```

        ## Search

        Full-text search across the searchable columns with the `search` param:

        ```bash
        {$this->base}/{repository}?search=laravel
        ```

        ## Filtering (IMPORTANT)

        Restify applies filters as **direct query params**, using the filter name as the key:

        ```bash
        {$this->base}/{repository}?status=active&priority=high
        ```

        Do **NOT** use the bracketed `filter[name]=value` convention — Restify does not read filters that way. Always pass `filterName=value` directly.
        MD;
    }

    private function renderRepositorySection(string $key): string
    {
        $repository = McpTools::getRepositoryOperations($key);

        $heading = "### `{$key}`";

        $description = trim((string) ($repository['description'] ?? ''));
        $intro = $description === '' ? '' : $description."\n";

        $operations = $this->renderOperations($repository);
        $schema = $this->renderFieldSchema($key, $repository);
        $query = $this->renderQueryCapabilities($key);
        $examples = $this->renderExamples($key, $repository);

        return collect([$heading, $intro, $operations, $schema, $query, $examples])
            ->map(fn (string $part): string => trim($part))
            ->filter(fn (string $part): bool => $part !== '')
            ->implode("\n\n");
    }

    private function renderOperations(array $repository): string
    {
        $operations = collect($repository['operations'])
            ->pluck('type')
            ->map(fn (string $type): string => "`{$type}`");

        $actions = collect($repository['actions'])
            ->map(fn (array $action): string => "`action:{$action['name']}`");

        $getters = collect($repository['getters'])
            ->map(fn (array $getter): string => "`getter:{$getter['name']}`");

        $available = $operations->concat($actions)->concat($getters)->implode(', ');

        return "**Operations:** {$available}";
    }

    private function renderFieldSchema(string $key, array $repository): string
    {
        $schema = $this->writeSchema($key, $repository);

        if ($schema === null) {
            return '';
        }

        $properties = $schema['properties'] ?? [];

        if ($properties === []) {
            return '';
        }

        $required = $schema['required'] ?? [];

        $lines = collect($properties)
            ->map(function (array $property, string $name) use ($required): string {
                $type = $property['type'] ?? 'string';
                $type = is_array($type) ? implode('|', $type) : $type;
                $flag = in_array($name, $required, true) ? 'required' : 'optional';

                return "- `{$name}` — {$type}, {$flag}";
            })
            ->implode("\n");

        return "**Fields:**\n\n".$lines;
    }

    private function renderQueryCapabilities(string $key): string
    {
        $class = Restify::repositoryClassForKey($key);

        if ($class === null) {
            return '';
        }

        $matches = array_keys($class::matches());
        $sortables = $this->queryParamIdentifiers($class::sorts());
        $searchables = $this->queryParamIdentifiers($class::searchables());
        $related = $this->queryParamIdentifiers($class::related());

        $lines = collect([
            'Matchables (filters)' => $matches,
            'Sortables' => $sortables,
            'Searchables' => $searchables,
            'Related' => $related,
        ])
            ->filter(fn (array $values): bool => $values !== [])
            ->map(function (array $values, string $label): string {
                $formatted = collect($values)
                    ->map(fn ($value, $key): string => '`'.($this->queryParamIdentifier($value, $key)).'`')
                    ->implode(', ');

                return "- **{$label}:** {$formatted}";
            })
            ->implode("\n");

        return $lines === '' ? '' : $lines;
    }

    private function queryParamIdentifiers(array $values): array
    {
        return collect($values)
            ->map(fn ($value, $key): string => $this->queryParamIdentifier($value, $key))
            ->values()
            ->all();
    }

    private function queryParamIdentifier(mixed $value, int|string $key): string
    {
        return is_string($value) ? $value : (string) $key;
    }

    private function renderExamples(string $key, array $repository): string
    {
        $examples = collect();

        $matches = array_keys(($class = Restify::repositoryClassForKey($key)) ? $class::matches() : []);
        $indexQuery = $matches === [] ? '?perPage=10' : '?'.$matches[0].'=value';

        $examples->push(implode("\n", [
            '# List (with a filter)',
            'curl -H "Authorization: Bearer $RESTIFY_TOKEN" \\',
            '     -H "Accept: application/json" \\',
            "     \"{$this->base}/{$key}{$indexQuery}\"",
        ]));

        if ($this->hasOperation($repository, 'show')) {
            $examples->push(implode("\n", [
                '# Show a single record',
                'curl -H "Authorization: Bearer $RESTIFY_TOKEN" \\',
                '     -H "Accept: application/json" \\',
                "     \"{$this->base}/{$key}/1\"",
            ]));
        }

        if ($this->hasOperation($repository, 'store')) {
            $body = json_encode($this->sampleBody($key, $repository), JSON_UNESCAPED_SLASHES);

            $examples->push(implode("\n", [
                '# Create a record',
                "curl -X POST \"{$this->base}/{$key}\" \\",
                '     -H "Authorization: Bearer $RESTIFY_TOKEN" \\',
                '     -H "Content-Type: application/json" \\',
                '     -H "Accept: application/json" \\',
                "     -d '{$body}'",
            ]));
        }

        return "**Examples:**\n\n```bash\n".$examples->implode("\n\n")."\n```";
    }

    /**
     * @return array<string, array>
     */
    private function repositoryPaths(string $key): array
    {
        $repository = McpTools::getRepositoryOperations($key);

        $collection = "{$this->base}/{$key}";
        $single = "{$this->base}/{$key}/{repositoryId}";

        $paths = [];

        if ($this->hasOperation($repository, 'index')) {
            $paths[$collection]['get'] = $this->operationSpec($key, 'List '.$key, [
                $this->queryParam('page', 'integer'),
                $this->queryParam('perPage', 'integer'),
                $this->queryParam('sort', 'string'),
                $this->queryParam('search', 'string'),
                $this->queryParam('related', 'string'),
            ]);
        }

        if ($this->hasOperation($repository, 'store')) {
            $paths[$collection]['post'] = $this->operationSpec($key, 'Create '.$key, [], $this->writeSchema($key, $repository));
        }

        if ($this->hasOperation($repository, 'show')) {
            $paths[$single]['get'] = $this->operationSpec($key, 'Show '.$key, [$this->pathIdParam()]);
        }

        if ($this->hasOperation($repository, 'update')) {
            $spec = $this->operationSpec($key, 'Update '.$key, [$this->pathIdParam()], $this->writeSchema($key, $repository));
            $paths[$single]['put'] = $spec;
            $paths[$single]['patch'] = $spec;
        }

        if ($this->hasOperation($repository, 'delete')) {
            $paths[$single]['delete'] = $this->operationSpec($key, 'Delete '.$key, [$this->pathIdParam()]);
        }

        if ($repository['actions'] !== []) {
            $actionKeys = collect($repository['actions'])->pluck('name')->implode(', ');
            $paths["{$this->base}/{$key}/actions"]['post'] = $this->operationSpec($key, "Run an action ({$actionKeys})", [
                $this->queryParam('action', 'string', true),
            ]);
        }

        foreach ($repository['getters'] as $getter) {
            $paths["{$this->base}/{$key}/getters/{$getter['name']}"]['get'] = $this->operationSpec(
                $key,
                (string) ($getter['description'] ?: "Getter {$getter['name']}"),
            );
        }

        return $paths;
    }

    private function operationSpec(string $tag, string $summary, array $parameters = [], ?array $requestSchema = null): array
    {
        $spec = [
            'tags' => [$tag],
            'summary' => $summary,
            'responses' => [
                '200' => ['description' => 'Successful response'],
            ],
        ];

        if ($parameters !== []) {
            $spec['parameters'] = $parameters;
        }

        if ($requestSchema !== null) {
            $spec['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => $requestSchema,
                    ],
                ],
            ];
        }

        return $spec;
    }

    private function queryParam(string $name, string $type, bool $required = false): array
    {
        return [
            'name' => $name,
            'in' => 'query',
            'required' => $required,
            'schema' => ['type' => $type],
        ];
    }

    private function pathIdParam(): array
    {
        return [
            'name' => 'repositoryId',
            'in' => 'path',
            'required' => true,
            'schema' => ['type' => 'string'],
        ];
    }

    private function writeSchema(string $key, array $repository): ?array
    {
        $type = match (true) {
            $this->hasOperation($repository, 'store') => 'store',
            $this->hasOperation($repository, 'update') => 'update',
            default => null,
        };

        if ($type === null) {
            return null;
        }

        $details = McpTools::getOperationDetails($key, $type);

        return (new JsonSchemaTypeFactory)->object($details['schema'])->toArray();
    }

    private function sampleBody(string $key, array $repository): array
    {
        $schema = $this->writeSchema($key, $repository);

        $properties = $schema['properties'] ?? [];

        return collect($properties)
            ->map(function (array $property): mixed {
                $type = $property['type'] ?? 'string';
                $type = is_array($type) ? ($type[0] ?? 'string') : $type;

                return match ($type) {
                    'integer', 'number' => 1,
                    'boolean' => true,
                    'array' => [],
                    'object' => new \stdClass,
                    default => 'value',
                };
            })
            ->toArray();
    }

    private function hasOperation(array $repository, string $type): bool
    {
        return collect($repository['operations'])->contains(fn (array $operation): bool => $operation['type'] === $type);
    }
}
