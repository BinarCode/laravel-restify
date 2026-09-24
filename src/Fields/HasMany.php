<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class HasMany extends EagerField
{
    protected $canEnableRelationshipCallback;

    public function __construct($relation, ?string $parentRepository = null)
    {
        parent::__construct($relation, $parentRepository);

        $this->readonly();
    }

    /**
     * @param  Repository  $repository
     * @return $this|EagerField|HasMany
     */
    public function resolve($repository, $attribute = null)
    {
        /** @var class-string<Repository> $repositoryClass */
        $repositoryClass = $this->repositoryClass;

        if ($repository->model()->relationLoaded($this->relation)) {
            $paginator = $repository->model()->getRelation($this->relation);
        } else {
            /**
             * @var Relation $paginator
             */
            $paginator = $repository->{$this->relation}();
            $paginator = $paginator
                ->take($this->relatablePerPage($repositoryClass::$defaultRelatablePerPage))
                ->select($this->getColumns())
                ->get();
        }

        $this->value = $paginator->map(function (Model $item) use ($repositoryClass) {
            try {
                return $repositoryClass::resolveWith($item)
                    ->allowToShow(app(Request::class))
                    ->eager($this);
            } catch (AuthorizationException) {
                return null;
            }
        });

        return $this;
    }

    public function fillAttribute(RestifyRequest $request, $model, ?int $bulkRow = null)
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
