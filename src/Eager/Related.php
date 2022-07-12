<?php

namespace Binaryk\LaravelRestify\Eager;

use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Traits\HasColumns;
use Binaryk\LaravelRestify\Traits\HasNested;
use Binaryk\LaravelRestify\Traits\Make;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonSerializable;
use ReturnTypeWillChange;

class Related implements JsonSerializable
{
    use Make;
    use HasColumns;
    use HasNested;

    private string $relation;

    /**
     * This is the default value.
     *
     * @var callable|string|int
     */
    private $value;

    private ?EagerField $field;

    /**
     * @var callable
     */
    private $resolverCallback;

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
        return $this
            ->field
            ->columns($this->getColumns())
            ->nested(Arr::wrap($this->nested ?: []))
            ->resolve($repository);
    }

    public function resolve(RestifyRequest $request, Repository $repository): self
    {
        $request->related()->resolved($repository::uriKey() . $repository->getKey() . $this->getRelation());

        if (is_callable($this->resolverCallback)) {
            $this->value = call_user_func($this->resolverCallback, $request, $repository);

            return $this;
        }

        if (Str::contains($this->getRelation(), '.')) {
            $repository->resource->loadMissing($this->getRelation());

            $key = Str::before($this->getRelation(), '.');

            $this->value = Arr::get($repository->resource->relationsToArray(), $key);

            return $this;
        }

        /** * To avoid circular relationships and deep stack calls, we will do not load eager fields. */
        if ($this->isEager()) {
            $this->value = $this->resolveField($repository)->value;

            return $this;
        }

        $paginator = $repository->resource->relationLoaded($this->getRelation())
            ? $repository->resource->{$this->getRelation()}
            : $repository->resource->{$this->getRelation()}();

        if (is_null($paginator)) {
            $this->value = null;

            return $this;
        }

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
                $this->value = $paginator;
        }

        return $this;
    }

    public function resolveUsing(callable $resolver): self
    {
        $this->resolverCallback = $resolver;

        return $this;
    }

    #[ReturnTypeWillChange]
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
