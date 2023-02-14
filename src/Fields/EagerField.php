<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Filters\RelatedQuery;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Traits\HasColumns;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EagerField extends Field
{
    use HasColumns;

    /**
     * Name of the relationship.
     */
    public string $relation;

    /**
     * The class name of the related repository.
     */
    public string $repositoryClass;

    private RelatedQuery $relatedQuery;

    public function __construct($attribute, string $parentRepository = null)
    {
        parent::__construct(attribute: $attribute);

        $this->relation = $attribute;

        if (is_string($parentRepository)) {
            $this->repositoryClass = $parentRepository;
        }

        if (is_null($parentRepository)) {
            $this->repositoryClass = tap(Restify::repositoryClassForKey(str($attribute)->pluralStudly()->kebab()->toString()),
                fn ($repository) => abort_unless($repository, 400, "Repository not found for the key [$attribute]."));
        }

        if (! isset($this->repositoryClass)) {
            abort(400, "Invalid parent repository [{$parentRepository}]. Expended instance of ".Repository::class);
        }
    }

    /**
     * Determine if the field should be displayed for the given request.
     *
     * @return bool
     */
    public function authorize(Request $request)
    {
        return call_user_func(
            [$this->repositoryClass, 'authorizedToUseRepository'],
            $request
        ) && parent::authorize($request);
    }

    public function resolve($repository, $attribute = null)
    {
        /** * @var Model $model */
        $model = $repository->resource;

        $relatedModel = $model->relationLoaded($this->relation)
            ? $model->{$this->relation}
            : $model->{$this->relation}()->select($this->getColumns())->first();

        if (is_null($relatedModel)) {
            $this->value = null;

            return $this;
        }

        try {
            /**
             * @var Repository $serializableRepository
             */
            $serializableRepository = $this->repositoryClass::resolveWith($relatedModel);

            $this->value = $serializableRepository
                ->allowToShow(app(Request::class))
                ->columns()
                ->eager($this);
        } catch (AuthorizationException) {
            $class = get_class($relatedModel);
            $field = class_basename(get_called_class());
            $policy = get_class(Gate::getPolicyFor($relatedModel));

            abort(
                403,
                "You are not authorized to see the [{$class}] relationship from the {$field} field from the {$field} field. Check the [show] method from the [$policy]"
            );
        }

        return $this;
    }

    public function getRelation(
        Repository $repository = null
    ): Relation {
        $repository = $repository ?? $this->parentRepository;

        return $repository->resource->{$this->relation}();
    }

    public function getRelatedModel(
        Repository $repository
    ): Model {
        return $this->getRelation($repository)->getRelated();
    }

    public function getRelatedKey(
        Repository $repository
    ): string {
        return $repository->resource->qualifyColumn(
            $repository->resource->{$this->relation}()->getForeignKeyName()
        );
    }

    public function getQualifiedKey(
        Repository $repository
    ): string {
        return $this->getRelation($repository)->getRelated()->getQualifiedKeyName();
    }

    public function withRelatedQuery(RelatedQuery $relatedQuery): self
    {
        $this->relatedQuery = $relatedQuery;

        return $this;
    }

    public function queryKeyThatRendered(): string
    {
        return $this->relatedQuery->relation;
    }
}
