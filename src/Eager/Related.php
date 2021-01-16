<?php

namespace Binaryk\LaravelRestify\Eager;

use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Resolvable;
use Binaryk\LaravelRestify\Traits\Make;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonSerializable;

class Related implements JsonSerializable, Resolvable
{
    use Make;

    private string $relation;

    /**
     * This is the default value.
     *
     * @var callable|string|int
     */
    private $value;

    private ?EagerField $field;

    public function __construct(string $relation, EagerField $field = null)
    {
        $this->relation = $relation;
        $this->field = $field;
    }

    public function isEager(): bool
    {
        return ! is_null($this->field);
    }

    public function getRelation(): string
    {
        return $this->relation;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function resolveField(Repository $repository): EagerField
    {
        return $this->field->resolve($repository);
    }

    public function resolve(RestifyRequest $request, Repository $repository): self
    {
        if (Str::contains($this->getRelation(), '.')) {
            $repository->resource->loadMissing($this->getRelation());

            $key = Str::before($this->getRelation(), '.');

            $this->value = Arr::get($repository->resource->relationsToArray(), $key);

            return $this;
        }

        /** * To avoid circular relationships and deep stack calls, we will do not load eager fields. */
        if ($this->isEager() && $repository->isEagerState() === false) {
            $this->value = $this->resolveField($repository)->value;

            return $this;
        }

        $paginator = $repository->resource->relationLoaded($this->getRelation())
            ? $repository->resource->{$this->getRelation()}
            : $repository->resource->{$this->getRelation()}();

        switch ($paginator) {
            case $paginator instanceof Builder:
                $this->value = ($repository::$relatedCast)::fromBuilder($request, $paginator, $repository);
                break;
            case $paginator instanceof Relation:
                $this->value = ($repository::$relatedCast)::fromRelation($request, $paginator, $repository);
                break;
            case $paginator instanceof Collection:
                $this->value = $paginator;
                break;
            default:
                dd('here');
                $this->value = $paginator;
        }

        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'relation' => $this->getRelation(),
            'field' => isset($this->field)
                ? $this->field->jsonSerialize()
                : null,
        ];
    }
}
