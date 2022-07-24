<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class HasMany extends EagerField
{
    protected $canEnableRelationshipCallback;

    public function __construct($relation, string $parentRepository = null)
    {
        parent::__construct($relation, $parentRepository);

        $this->readonly();
    }

    /**
     * @param  Repository  $repository
     * @param  null  $attribute
     * @return $this|EagerField|HasMany
     */
    public function resolve($repository, $attribute = null)
    {
        if ($repository->model()->relationLoaded($this->relation)) {
            $paginator = $repository->model()->getRelation($this->relation);
        } else {
            $paginator = $repository->{$this->relation}();
            $paginator = $paginator
                ->take(request('relatablePerPage') ?? ($this->repositoryClass::$defaultRelatablePerPage ?? RestifySearchable::DEFAULT_RELATABLE_PER_PAGE))
                ->select($this->getColumns())
                ->get();
        }

        $this->value = $paginator->map(function ($item) {
            try {
                return $this->repositoryClass::resolveWith($item)
                    ->allowToShow(app(Request::class))
                    ->eager($this);
            } catch (AuthorizationException) {
                return null;
            }
        });

        return $this;
    }

    public function fillAttribute(RestifyRequest $request, $model, int $bulkRow = null)
    {
        //
    }

    public function canEnableRelationship($callback = true)
    {
        $this->canEnableRelationshipCallback = $callback;

        return $this;
    }

    public function authorizedToEnableRelationship(Request $request): bool
    {
        if (! isset($this->canEnableRelationshipCallback)) {
            return false;
        }

        return is_callable($this->canEnableRelationshipCallback)
            ? call_user_func($this->canEnableRelationshipCallback, $request)
            : $this->canEnableRelationshipCallback;
    }
}
