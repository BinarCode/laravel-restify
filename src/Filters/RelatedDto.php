<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Http\Requests\RequestLimits;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Stringable;

class RelatedDto
{
    public RelatedQueryCollection $related;

    public array $nested = [];

    public array $resolvedRelationships = [];

    public array $relatedArray = [];

    private bool $loaded = false;

    public string $rootKey = '';

    public function __construct(
        ?RelatedQueryCollection $related = null,
    ) {
        $this->related = $related ?? RelatedQueryCollection::make([]);
    }

    public function hasRelated(): bool
    {
        return ! empty($this->related);
    }

    /**
     * Dot notation of the relationship. Could be a nested relation users.posts.tags
     */
    public function hasRelation(string $relation): bool
    {
        return (bool) $this->getRelatedQueryFor($relation);
    }

    public function getColumnsFor(string $relation): array
    {
        return $this->getRelatedQueryFor($relation)?->columns() ?: ['*'];
    }

    public function getRelatedQueryFor(string $relation): ?RelatedQuery
    {
        return collect($this->relatedArray)->first(fn ($object, $key) => str_contains($key, $relation));
    }

    public function getNestedFor(string $relation): ?RelatedQueryCollection
    {
        return $this->getRelatedQueryFor($relation)?->nested;
    }

    public function resolved(string $relationship): self
    {
        $this->resolvedRelationships[] = $relationship;

        return $this;
    }

    public function isResolved(string $relationship): bool
    {
        return array_key_exists($relationship, $this->resolvedRelationships);
    }

    public function reset(): self
    {
        $this->loaded = false;

        $this->related = RelatedQueryCollection::make([]);

        $this->resolvedRelationships = [];

        $this->relatedArray = [];

        return $this;
    }

    private function searchInRelatedQuery(RelatedQuery $relatedQuery, string $relation): ?RelatedQuery
    {
        if ($relatedQuery->matchTree($relation)) {
            return $relatedQuery;
        }

        if ($relatedQuery->nested->count()) {
            return $relatedQuery->nested->first(fn (RelatedQuery $child) => $this->searchInRelatedQuery(
                $child,
                $relation
            ));
        }

        return null;
    }

    private function makeTreeForChild(RelatedQuery $relatedQuery, array &$base = []): array
    {
        if (! $relatedQuery->nested->count()) {
            return [$relatedQuery->relation];
        }

        $relatedQuery->nested->each(function (RelatedQuery $child, $i) use (&$base, $relatedQuery) {
            $base[$i] = data_get($base, $i, $relatedQuery->relation).".$child->relation";

            if ($child->nested->count()) {
                $this->makeTreeForChild($child, $base);
            }
        });

        return $base;
    }

    /**
     * @return array<int, string>
     */
    public function makeTree(): array
    {
        return collect(array_keys($this->relatedArray))
            ->mapInto(Stringable::class)
            ->map(fn (Stringable $relation) => $relation->after('.'))
            ->unique()
            ->map(fn (Stringable $relation) => $relation->toString())
            ->all();
    }

    public function sync(Request $request, Repository $repository): self
    {
        $this->rootKey = $repository::uriKey();

        if ($this->loaded) {
            return $this;
        }

        if (empty($query = $this->query($request))) {
            $this->loaded();

            return $this;
        }

        $roots = str($query)->replace(' ', '')->explode(',');

        $maxDepth = RequestLimits::maxRelatedDepth();
        $declared = $maxDepth === null ? [] : $this->declaredPathsDeeperThan($repository, $maxDepth);

        collect($roots)->each(function (string $related) use ($repository, $maxDepth, $declared) {
            $related = $this->limitDepth($related, $maxDepth, $declared);

            if (str_contains($related, '.')) {
                // users[id].comments[id] => users
                $relation = str(collect(str($related)->explode('.'))->first())->before('[');
            } else {
                // comments[id] => comments
                // comments => comments
                $relation = str($related)->before('[');
            }

            /**
             * @var RelatedQuery|null $relatedQuery
             */
            if ($relatedQuery = $this->related->firstWhere('relation', $relation)) {
                $parent = $relatedQuery;
            } else {
                $parent = RelatedQuery::fromToken(str($related)->before('.'))->parent($repository::uriKey());
                $this->relatedArray[$parent->tree] = clone $parent;
            }

            // Here it's like `comments[id]`
            if (! str_contains($related, '.')) {
                /**
                 * @var RelatedQuery|null $relatedQuery
                 */
                if ($relatedQuery = $this->related->firstWhere('relation', $relation)) {
                    $relatedQuery->nested->push($parent);
                } else {
                    $this->related->push($parent);
                }

                $this->loaded();

                return $this;
            }

            /**
             * @var RelatedQuery|null $relatedQuery
             */
            if (! $this->related->firstWhere('relation', $relation)) {
                $this->related->push($parent);
            }

            collect(str($related)->after('.')->explode('.'))->each(function (string $nested) use (&$parent): void {
                $newParent = RelatedQuery::fromToken($nested)->parent($parent->tree);

                $this->relatedArray[$newParent->tree] = $newParent;

                $parent->nested->push(
                    $parent = $newParent
                );
            });

            $this->loaded();

            return $this;
        });

        $this->loaded();

        return $this;
    }

    /**
     * Cut a requested path to `restify.related.max_depth` segments, so with a limit of 2
     * `posts.comments.user` loads `posts.comments`. A path the repository declares itself is
     * kept in full, since only its full form is declared.
     *
     * @param  list<string>  $declared
     */
    private function limitDepth(string $related, ?int $maxDepth, array $declared): string
    {
        if ($maxDepth === null || substr_count($related, '.') < $maxDepth) {
            return $related;
        }

        if ($declared !== []) {
            // posts[id|title].comments[comment] => posts.comments
            $path = preg_replace('/\[[^\]]*\]/', '', $related) ?? $related;

            foreach ($declared as $declaredPath) {
                if ($path === $declaredPath || str_starts_with($path, $declaredPath.'.')) {
                    $maxDepth = max($maxDepth, substr_count($declaredPath, '.') + 1);
                }
            }
        }

        return implode('.', array_slice(explode('.', $related), 0, $maxDepth));
    }

    /**
     * The dotted paths the repository declares in its related list that are longer than the limit.
     *
     * @return list<string>
     */
    private function declaredPathsDeeperThan(Repository $repository, int $maxDepth): array
    {
        $paths = [];

        foreach ($repository::collectRelated()->all() as $key => $value) {
            $path = is_string($key) ? $key : $value;

            if (is_string($path) && substr_count($path, '.') >= $maxDepth) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    private function loaded(): void
    {
        $this->loaded = true;
    }

    private function query(Request $request): ?string
    {
        return $request->input('related') ?? $request->input('include');
    }
}
