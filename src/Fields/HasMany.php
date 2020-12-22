<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HasMany extends EagerField
{
    protected $canEnableRelationshipCallback;

    public function __construct($attribute, $relation, $parentRepository)
    {
        if (! is_a(app($parentRepository), Repository::class)) {
            abort(500, "Invalid parent repository [{$parentRepository}]. Expended instance of ".Repository::class);
        }

        parent::__construct($attribute);

        $this->relation = $relation;
        $this->repositoryClass = $parentRepository;

        $this->readonly();
    }

    public function resolve($repository, $attribute = null)
    {
        $paginator = $repository->{$this->relation}();

        $paginator = $paginator->take(request('relatablePerPage') ?? ($this->repositoryClass::$defaultRelatablePerPage ?? RestifySearchable::DEFAULT_RELATABLE_PER_PAGE))->get();

        $this->value = $paginator->map(function ($item) {
            try {
                return $this->repositoryClass::resolveWith($item)
                    ->allowToShow(app(Request::class))
                    ->eagerState();
            } catch (AuthorizationException $e) {
                $class = get_class($item);
                $policy = get_class(Gate::getPolicyFor($item));

                abort(403, "You are not authorized to see the [{$class}] relationship from the HasMany field from the BelongsTo field. Check the [show] method from the [$policy]");
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
