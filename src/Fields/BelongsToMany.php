<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Fields\Concerns\Attachable;
use Binaryk\LaravelRestify\Repositories\PivotsCollection;
use Binaryk\LaravelRestify\Repositories\Repository;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class BelongsToMany extends EagerField
{
    use Attachable;

    /**
     * Callback used to attach.
     *
     * @var Closure
     */
    public $attachCallback;

    /**
     * Callback used to detach.
     *
     * @var Closure
     */
    public $detachCallback;

    public function __construct($relation, $parentRepository)
    {
        if (! is_a(app($parentRepository), Repository::class)) {
            abort(500, "Invalid parent repository [{$parentRepository}]. Expended instance of ".Repository::class);
        }

        parent::__construct(attribute: $relation);

        $this->relation = $relation;
        $this->repositoryClass = $parentRepository;

        $this->readonly();
    }

    public function resolve($repository, $attribute = null)
    {
        /**
         * @var Repository $repository
         */
        if ($repository->model()->relationLoaded($this->relation)) {
            $paginator = $repository->model()->getRelation($this->relation);
        } else {
            $paginator = $repository->{$this->relation}();

            $paginator = $paginator->take(request('relatablePerPage') ?? ($repository::$defaultRelatablePerPage ?? RestifySearchable::DEFAULT_RELATABLE_PER_PAGE))->get();
        }

        $this->value = $paginator->map(function ($item) {
            try {
                return $this->repositoryClass::resolveWith($item)
                    ->allowToShow(app(Request::class))
                    ->withPivots(
                        PivotsCollection::make($this->pivotFields)
                            ->map(fn (Field $field) => clone $field)
                            ->resolveFromPivot($item->pivot)
                    )
                    ->eager($this);
            } catch (AuthorizationException) {
                return null;
            }
        });

        return $this;
    }

    public function attachCallback(callable|Closure $callback)
    {
        $this->attachCallback = $callback;

        return $this;
    }

    public function detachCallback(callable|Closure $callback)
    {
        $this->detachCallback = $callback;

        return $this;
    }
}
