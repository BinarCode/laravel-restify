<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Spatie\DataTransferObject\DataTransferObject;

class RelatedDto extends DataTransferObject
{
    public ?RelatedQueryCollection $related = null;

    public array $nested = [];

    public array $resolvedRelationships = [];

    private bool $loaded = false;

    public function getColumnsFor(string $relation): array|string
    {
        return $this->getRelatedQueryFor($relation)?->columns() ?: '*';
    }

    public function getRelatedQueryFor(string $relation): ?RelatedQuery
    {
        return $this->related->firstWhere('relation', $relation);
    }

    public function getNestedForRelatedForRelation(RelatedQuery $relatedQuery, string $relation)
    {

    }

    public function getNestedFor(string $relation): ?RelatedQueryCollection
    {
        return $this->getRelatedQueryFor($relation)?->nested;
    }

    public function normalize(): self
    {
        $this->related = collect($this->related)->map(function (string $relationship) {
            if (str($relationship)->contains('.')) {
                $baseRelationship = str($relationship)->before('.')->toString();

                $this->nested[$baseRelationship][] = (new RelatedDto(
                    related: [
                        str($relationship)
                            ->after($baseRelationship)
                            ->whenStartsWith('.', fn (Stringable $string) => $string->replaceFirst('.', ''))
                            ->ltrim()
                            ->rtrim()
                            ->toString(),
                    ]
                ))
                    ->normalize();

                return $baseRelationship;
            }

            return $relationship;
        })->unique()->all();

        return $this;
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

    public function sync(RestifyRequest $request): self
    {
        if (empty($query = ($request->input('related') ?? $request->input('include')))) {
            $this->loaded = true;

            return $this;
        }

        if (! $this->loaded) {
            $this->related = collect(str_getcsv($query))->mapInto(Stringable::class)->map->ltrim()->map->rtrim()->all();

            $this->normalize();

            $this->loaded = true;
        }

        return $this;
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

    public function makeTree(): array
    {
        return collect($this->related)->map(
            fn (string $relation) => collect($this->nested[$relation] ?? [null])->map(
                fn (?self $nested) => $this->makeTreeFor($relation, $nested)
            )
        )->flatten()->all();
    }

    public function hasRelated(): bool
    {
        return ! empty($this->related);
    }

    public static function makeFromRequest(Request $request): self
    {
        $instance = new static(related: RelatedQueryCollection::make([]));

        if (empty($query = ($request->input('related') ?? $request->input('include')))) {
            return $instance;
        }

        $roots = str($query)->replace(' ', '')->explode(',');

        collect($roots)->map(function (string $related) use ($instance) {
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
            if ($relatedQuery = $instance->related->firstWhere('relation', $relation)) {
                $parent = $relatedQuery;
            } else {
                $parent = RelatedQueryCollection::fromToken(str($related)->before('.'));
            }

            // Here it's like `comments[id]`
            if (! str($related)->contains('.')) {
                /**
                 * @var RelatedQuery|null $relatedQuery
                 */
                if ($relatedQuery = $instance->related->firstWhere('relation', $relation)) {
                    $relatedQuery->nested->push($parent);
                } else {
                    $instance->related->push($parent);
                }

                return $instance;
            }

            /**
             * @var RelatedQuery|null $relatedQuery
             */
            if (! $instance->related->firstWhere('relation', $relation)) {
                $instance->related->push($parent);
            }

            collect(str($related)->after('.')->explode('.'))
                ->map(function (string $nested, $i) use ($parent) {
                    if ($i === 0) {
                        return $parent->nested->push(RelatedQueryCollection::fromToken($nested));
                    }

                    return $parent->nested->nth($i)->first()->nested->push(RelatedQueryCollection::fromToken($nested));
                });

            return $instance;
        });

        return $instance;
    }
}
