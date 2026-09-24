<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpRequestable;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends Collection<TKey, TValue>
 */
class FieldCollection extends Collection
{
    public function authorized(Request $request): self
    {
        return $this->filter(function (OrganicField $field) use ($request) {
            return $field->authorize($request);
        })->values();
    }

    public function authorizedUpdate(Request $request): self
    {
        return $this->filter(function (OrganicField $field) use ($request) {
            return $field->authorizedToUpdate($request);
        })->values();
    }

    public function authorizedPatch(Request $request): self
    {
        return $this->filter(function (OrganicField $field) use ($request) {
            return $field->authorizedToPatch($request);
        })->values();
    }

    public function authorizedUpdateBulk(Request $request): self
    {
        return $this->filter(function (OrganicField $field) use ($request) {
            return $field->authorizedToUpdateBulk($request);
        })->values();
    }

    public function authorizedStore(Request $request): self
    {
        return $this->filter(function (OrganicField $field) use ($request) {
            return $field->authorizedToStore($request);
        })->values();
    }

    public function resolve($repository): self
    {
        return $this->each(function ($field) use ($repository) {
            $field->resolve($repository);
        });
    }

    public function forIndex(RestifyRequest $request, $repository): self
    {
        return $this
            ->filter(fn (Field $field) => ! $field instanceof EagerField)
            ->filter(function (Field $field) use ($repository, $request) {
                return $field->isShownOnIndex($request, $repository);
            })->values();
    }

    public function forMcpIndex(RestifyRequest $request, $repository): self
    {
        // If this is an MCP request and repository has fieldsForMcpIndex method
        if ($request instanceof McpRequestable && method_exists($repository, 'fieldsForMcpIndex')) {
            // Get the MCP-specific fields from the repository
            $mcpFields = $repository->fieldsForMcpIndex($request);
            $mcpFieldAttributes = collect($mcpFields)->map(fn ($field) => $field->attribute)->toArray();

            // Filter the current collection to only include MCP fields
            return $this
                ->filter(fn (Field $field) => ! $field instanceof EagerField)
                ->filter(fn (Field $field) => in_array($field->attribute, $mcpFieldAttributes))
                ->filter(function (Field $field) use ($repository, $request) {
                    return $field->isShownOnMcp($request, $repository);
                })->values();
        }

        // Fallback to regular index filtering for non-MCP requests
        return $this->forIndex($request, $repository);
    }

    public function forShow(RestifyRequest $request, $repository): self
    {
        return $this
            ->filter(fn (Field $field) => ! $field instanceof EagerField)
            ->filter(function (Field $field) use ($repository, $request) {
                return $field->isShownOnShow($request, $repository);
            })->values();
    }

    public function forStore(RestifyRequest $request, $repository): self
    {
        return $this
            ->filter(fn (Field $field) => ! $field instanceof EagerField)
            ->filter(function (Field $field) use ($repository, $request) {
                return $field->isShownOnStore($request, $repository);
            })->values();
    }

    public function withActions(RestifyRequest $request, $repository, $row = null): self
    {
        return $this
            ->inRequest($request, $row)
            ->filter(fn (Field $field) => $field->isActionable())
            ->values();
    }

    public function withoutActions(RestifyRequest $request, $repository): self
    {
        return $this
            ->reject(fn (Field $field) => $field->isActionable() && $field->actionHandler?->skipFieldFill($request))
            ->values();
    }

    public function forStoreBulk(RestifyRequest $request, $repository): self
    {
        return $this->filter(function (Field $field) use ($repository, $request) {
            return $field->isShownOnStoreBulk($request, $repository);
        })->values();
    }

    public function forUpdate(RestifyRequest $request, $repository): self
    {
        return $this
            ->filter(fn (Field $field) => ! $field instanceof EagerField)
            ->filter(function (Field $field) use ($repository, $request) {
                return $field->isShownOnUpdate($request, $repository);
            })->values();
    }

    public function forUpdateBulk(RestifyRequest $request, $repository): self
    {
        return $this->filter(function (Field $field) use ($repository, $request) {
            return $field->isShownOnUpdateBulk($request, $repository);
        })->values();
    }

    public function filterForManyToManyRelations(RestifyRequest $request): self
    {
        return $this->filter(function ($field) {
            return $field instanceof BelongsToMany || $field instanceof MorphToMany;
        })->filter(fn (EagerField $field) => $field->authorize($request));
    }

    public function forEager(RestifyRequest $request, Repository $repository): self
    {
        return $this
            ->filter(fn (Field $field) => $field instanceof EagerField)
            ->filter(fn (Field $field) => $field->authorize($request))
            ->unique();
    }

    public function forBelongsTo(RestifyRequest $request): self
    {
        return $this
            ->filter(fn (Field $field) => $field instanceof BelongsTo)
            ->unique();
    }

    public function setRepository(Repository $repository): self
    {
        return $this->each(fn (Field $field) => $field->setRepository($repository));
    }

    /**
     * Give every unlabeled computed field a unique, positional label - `Computed`,
     * `Computed_1`, `Computed_2`, ... - before any visibility or authorization filtering
     * runs, so the same field resolves to the same key on every request. A field already
     * labeled `Computed` or `Computed_N` (computed or not) reserves that key for itself.
     *
     * @return $this
     */
    public function assignComputedFieldLabels(): self
    {
        self::assignComputedLabels($this->all());

        return $this;
    }

    /**
     * The numbering half of {@see self::assignComputedFieldLabels()}, shared with
     * pivot fields (`BelongsToMany::resolve()`), which bypass `Repository::collectFields()`
     * and would otherwise let unlabeled computed pivot fields collide under the same key.
     *
     * @param  iterable<int|string, mixed>  $fields
     */
    public static function assignComputedLabels(iterable $fields): void
    {
        $unlabeled = [];

        foreach ($fields as $item) {
            if ($item instanceof Field && $item->computed() && $item->label === null) {
                $unlabeled[] = $item;
            }
        }

        if ($unlabeled === []) {
            return;
        }

        $taken = [];

        foreach ($fields as $item) {
            if (! $item instanceof Field || ($item->computed() && $item->label === null)) {
                continue;
            }

            self::reserveComputedKey(is_string($item->label) ? $item->label : null, $taken);

            if (! $item->computed()) {
                self::reserveComputedKey(is_string($item->attribute) ? $item->attribute : null, $taken);
            }
        }

        $next = 0;

        foreach ($unlabeled as $item) {
            while (isset($taken[$next])) {
                $next++;
            }

            $item->label($next === 0 ? 'Computed' : "Computed_{$next}");
            $taken[$next] = true;
            $next++;
        }
    }

    /**
     * @param  array<int, true>  $taken
     */
    private static function reserveComputedKey(?string $candidate, array &$taken): void
    {
        if ($candidate === null || ! str_starts_with($candidate, 'Computed') || preg_match('/^Computed(?:_([1-9]\d*))?$/', $candidate, $matches) !== 1) {
            return;
        }

        $taken[isset($matches[1]) ? (int) $matches[1] : 0] = true;
    }

    public function findFieldByAttribute($attribute, $default = null)
    {
        foreach ($this->items as $field) {
            if (isset($field->attribute) && $field->attribute === $attribute) {
                return $field;
            }
        }

        return null;
    }

    public function inRequest(RestifyRequest $request, $row = null): self
    {
        return $this
            ->filter(
                fn (Field $field) => $request->hasAny($field->attribute, $row.'.'.$field->attribute)
                    || $request->hasFile($field->attribute)
            )
            ->values();
    }

    public function inList(array $columns = []): self
    {
        return $this
            ->filter(fn (Field $field) => in_array($field->getAttribute(), $columns, true))
            ->values();
    }

    public function areFiles(): self
    {
        return $this
            ->filter(fn (Field $field) => $field instanceof File)
            ->values();
    }
}
