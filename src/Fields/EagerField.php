<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EagerField extends Field
{
    /**
     * Name of the relationship.
     *
     * @var string
     */
    protected string $relation;

    /**
     * The class name of the related repository.
     *
     * @var Repository
     */
    public string $repositoryClass;

    public function __construct($attribute, callable $resolveCallback = null)
    {
        parent::__construct($attribute, $resolveCallback);

        $this->showOnShow()
            ->hideFromIndex();
    }

    /**
     * Determine if the field should be displayed for the given request.
     *
     * @param Request $request
     * @return bool
     */
    public function authorize(Request $request)
    {
        return call_user_func(
                [$this->repositoryClass, 'authorizedToUseRepository'], $request
            ) && parent::authorize($request);
    }

    public function resolve($repository, $attribute = null)
    {
        $model = $repository->resource;

        $relatedModel = $model->{$this->relation}()->first();

        try {
            $this->value = $this->repositoryClass::resolveWith($relatedModel)
                ->allowToShow(app(Request::class))
                ->eagerState();
        } catch (AuthorizationException $e) {
            if (is_null($relatedModel)) {
                abort(403, 'Related model is null.');
            }

            $class = get_class($relatedModel);
            $field = class_basename(get_called_class());
            $policy = get_class(Gate::getPolicyFor($relatedModel));

            abort(403, "You are not authorized to see the [{$class}] relationship from the {$field} field from the {$field} field. Check the [show] method from the [$policy]");
        }

        return $this;
    }

    public function getRelation(Repository $repository): Relation
    {
        return $repository->resource->newQuery()
            ->getRelation($this->relation);
    }

    public function getRelatedModel(Repository $repository): ?Model
    {
        return $this->getRelation($repository)->getRelated();
    }

    public function getRelatedKey(Repository $repository): string
    {
        return $repository->resource->qualifyColumn(
            $this->getRelation($repository)->getRelated()->getForeignKey()
        );
    }

    public function getQualifiedKey(Repository $repository): string
    {
        return $this->getRelation($repository)->getRelated()->getQualifiedKeyName();
    }
}
