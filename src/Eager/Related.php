<?php

namespace Binaryk\LaravelRestify\Eager;

use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Filters\RelatedQuery;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Traits\HasColumns;
use Binaryk\LaravelRestify\Traits\Make;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonSerializable;
use ReturnTypeWillChange;

class Related implements JsonSerializable
{
    use HasColumns;
    use Make;

    private string $relation;

    /**
     * This is the default value.
     *
     * @var callable|string|int
     */
    private $value;

    public ?EagerField $field;

    /**
     * @var callable
     */
    private $resolverCallback;

    public ?RelatedQuery $relatedQuery = null;

    public function __construct(string $relation, ?EagerField $field = null)
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
            ->resolve($repository);
    }

    public function resolve(RestifyRequest $request, Repository $repository): self
    {
        $request->related()->resolved($this->uniqueIdentifierForRepository($repository));

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
            case $paginator instanceof Collection:
                $this->value = $this->serializeRelationshipData($request, $paginator);

                break;
            case $paginator instanceof BelongsTo:
                $relatedModel = $paginator->first();
                $this->value = $relatedModel ? $this->serializeRelationshipData($request, $relatedModel) : null;

                break;
            case $paginator instanceof Builder:
                $this->value = $this->serializeRelationshipData($request, $paginator->get());

                break;
            default:
                $this->value = $this->serializeRelationshipData($request, $paginator);
        }

        return $this;
    }

    public function resolveUsing(callable $resolver): self
    {
        $this->resolverCallback = $resolver;

        return $this;
    }

    /**
     * Serialize relationship data using repository field collections for MCP requests.
     */
    protected function serializeRelationshipData(RestifyRequest $request, $data)
    {
        // For non-MCP requests, return data as-is to maintain backward compatibility
        if (! $request instanceof McpRequest) {
            return $data;
        }

        // Handle null data
        if (is_null($data)) {
            return null;
        }

        // Handle single models
        if ($data instanceof \Illuminate\Database\Eloquent\Model) {
            return $this->serializeSingleModel($request, $data);
        }

        // Handle collections
        if ($data instanceof Collection) {
            return $data->map(function ($model) use ($request) {
                return $model instanceof \Illuminate\Database\Eloquent\Model
                    ? $this->serializeSingleModel($request, $model)
                    : $model;
            });
        }

        return $data;
    }

    /**
     * Serialize a single model using its repository's field collection for MCP requests.
     */
    protected function serializeSingleModel(RestifyRequest $request, \Illuminate\Database\Eloquent\Model $model): array
    {
        // Try to find the repository for this model
        $repositoryClass = Restify::repositoryForModel($model);

        if (! $repositoryClass) {
            // Fallback to model attributes if no repository found
            return $model->toArray();
        }

        try {
            // Create repository instance with the model
            $repository = $repositoryClass::resolveWith($model);
            $repository->request = $request;

            // Get the appropriate field collection for MCP index
            $fields = $repository->collectFields($request);

            // Serialize using repository fields
            $result = [];
            foreach ($fields as $field) {
                $field->resolveForIndex($repository);
                $serialized = $field->serializeToValue($request);
                $result = array_merge($result, $serialized);
            }

            return $result;
        } catch (\Exception $e) {
            // Fallback to model attributes if serialization fails
            return $model->toArray();
        }
    }

    public function withRelatedQuery(RelatedQuery $relatedQuery): self
    {
        $this->relatedQuery = $relatedQuery;

        return $this;
    }

    public function uniqueIdentifierForRepository(Repository $repository): string
    {
        return $repository::uriKey().$repository->getKey().$this->getRelation();
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
