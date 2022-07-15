<?php

namespace Binaryk\LaravelRestify\Filters;

use Illuminate\Http\Request;

class RelatedDto
{
    public RelatedQueryCollection $related;

    public array $nested = [];

    public array $resolvedRelationships = [];

    private bool $loaded = false;

    public function __construct(
        ?RelatedQueryCollection $related = null,
    ) {
        $this->related = $related ?? RelatedQueryCollection::make([]);
    }

    public function getColumnsFor(string $relation): array|string
    {
        return $this->getRelatedQueryFor($relation)?->columns() ?: '*';
    }

    public function getRelatedQueryFor(string $relation): ?RelatedQuery
    {
        return $this->related->firstWhere('relation', $relation);
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

        $this->resolvedRelationships = [];

        return $this;
    }

    private function makeTreeFor(string $related, ?RelatedDto $dto = null): string
    {
        if (is_null($dto)) {
            return $related;
        }

        $child = collect($dto->related)->first();

        return $this->makeTreeFor("$related.".$child, collect(data_get($dto->nested, $child))->first());
    }

    private function makeTreeFroChild(RelatedQuery $relatedQuery, array &$base = [])
    {
        if ($relatedQuery->nested->count()) {
            $relatedQuery->nested->each(function (RelatedQuery $child, $i) use (&$base, $relatedQuery) {
                $base[$i] = data_get($base, $i, $relatedQuery->relation).".$child->relation";

                if ($child->nested->count()) {
                    $this->makeTreeFroChild($child, $base);
                }
            });
        } else {
            return [$relatedQuery->relation];
        }

        return $base;
    }

    public function makeTree(Request $request): array
    {
        $data = [];

        return $this->related->map(function (RelatedQuery $relatedQuery) use ($data) {
            return $this->makeTreeFroChild($relatedQuery, $data);
        })->flatten()->all();
    }

    public function hasRelated(): bool
    {
        return !empty($this->related);
    }

    public function sync(Request $request): self
    {
        if ($this->loaded) {
            return $this;
        }

        if (empty($query = $this->query($request))) {
            $this->loaded();

            return $this;
        }

        $roots = str($query)->replace(' ', '')->explode(',');

        collect($roots)->map(function (string $related) {
            if (str($related)->contains('.')) {
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
                $parent = RelatedQueryCollection::fromToken(str($related)->before('.'));
            }

            // Here it's like `comments[id]`
            if (!str($related)->contains('.')) {
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
            if (!$this->related->firstWhere('relation', $relation)) {
                $this->related->push($parent);
            }

            collect(str($related)->after('.')->explode('.'))
                ->map(function (string $nested, $i) use ($parent) {
                    if ($i === 0) {
                        return $parent->nested->push(RelatedQueryCollection::fromToken($nested));
                    }

                    return $parent->nested->nth($i)->first()->nested->push(RelatedQueryCollection::fromToken($nested));
                });

            $this->loaded();

            return $this;
        });

        $this->loaded();

        return $this;
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
