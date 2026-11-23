<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use BackedEnum;
use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Filters\MatchesCollection;
use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Filters\PaginationDataObject;
use Binaryk\LaravelRestify\Filters\SearchablesCollection;
use Binaryk\LaravelRestify\MCP\Requests\McpIndexRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Services\Search\RepositorySearchService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Stringable;

/**
 * @mixin Repository
 */
trait McpIndexTool
{
    public function indexTool(McpIndexRequest $request): array
    {
        return $this->index($request)->getData(true);
    }

    /**
     * The index response narrowed to the given user: the fields, scopes and show policy of the index tool, holding
     * that one row or none.
     *
     * The key is never read from the request. It is the primary key of the user as a row of this repository, see
     * profileKey(): a user that is not one, such as an admin of another guard, gets the response without a row,
     * and the repository is not queried. The key is a scope of the query, so it is ANDed with the rest of it as a
     * whole even when a repository scope ends in an OR, and it is checked again on the rows read. The page and the
     * page size are fixed, and the response skips the index cache.
     */
    public function profileTool(McpIndexRequest $request, Authenticatable $user): array
    {
        $key = static::profileKey($user);

        $canonical = static fn (mixed $value): ?string => match (true) {
            $value instanceof BackedEnum => (string) $value->value,
            is_int($value), is_string($value), $value instanceof Stringable => (string) $value,
            default => null,
        };

        $ownKey = $canonical($key);

        $perPage = PaginationDataObject::fromInput(null, null)->resolvePerPage(static::$defaultPerPage);

        /** @var LengthAwarePaginator<int, Model> $paginator */
        $paginator = $key === null
            ? new LengthAwarePaginator([], 0, $perPage, 1, ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page'])
            : RepositorySearchService::make()->search($request, $this)
                ->withGlobalScope('restify-profile', fn (Builder $query) => $query->whereKey($key))
                ->paginate($perPage, page: 1);

        $paginator->setCollection(
            $paginator->getCollection()
                ->filter(fn (Model $model): bool => $ownKey !== null && $canonical($model->getKey()) === $ownKey)
                ->values()
        );

        return response()->json($this->serializeIndexPage($request, $paginator))->getData(true);
    }

    public static function indexToolSchema(JsonSchema $schema): array
    {
        $key = static::uriKey();

        $properties = [
            'page' => $schema->number()
                ->description('Page number for pagination'),

            'perPage' => $schema->number()
                ->description("Number of $key per page"),

            'include' => $schema->string()
                ->description(static::formatRelationshipDocumentation(app(McpIndexRequest::class))),
        ];

        $searchableFields = (new SearchablesCollection(static::searchables()))->fieldNames();

        if (! empty($searchableFields)) {
            $properties['search'] = $schema->string()
                ->description('Full-text search across: '.implode(', ', $searchableFields).' (search=term).');
        }

        $sortOptions = collect(static::sorts())
            ->map(function ($value, $key) {
                if (is_string($value)) {
                    return $value;
                }
                if (is_numeric($key)) {
                    return $value;
                }

                return $key;
            })
            ->filter(fn ($option) => is_string($option) && $option !== '')
            ->values()
            ->toArray();

        if (! empty($sortOptions)) {
            $properties['sort'] = $schema->string()
                ->description("Sort $key by one of: ".implode(', ', $sortOptions).". Prefix with '-' for descending (sort=field or sort=-field).");
        }

        MatchesCollection::make(static::matches())
            ->normalize()
            ->authorized(app(McpRequest::class))
            ->each(function (MatchFilter $matchFilter) use ($schema, &$properties) {
                $filterKey = $matchFilter->column();
                $description = $matchFilter->description();

                $properties[$filterKey] = match ($matchFilter->getType()) {
                    RestifySearchable::MATCH_INTEGER, 'integer' => $schema->integer()
                        ->description($description),
                    RestifySearchable::MATCH_BOOL, 'boolean' => $schema->boolean()
                        ->description($description),
                    default => $schema->string()
                        ->description($description)
                };
            });

        return $properties;
    }
}
