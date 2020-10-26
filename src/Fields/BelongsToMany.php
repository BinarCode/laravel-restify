<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Fields\Concerns\Attachable;
use Binaryk\LaravelRestify\Repositories\Repository;
use Closure;

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

        $paginator = $paginator->take(request('relatablePerPage') ?? ($repository::$defaultRelatablePerPage ?? RestifySearchable::DEFAULT_RELATABLE_PER_PAGE))->get();

        $this->value = $paginator->map(function ($item) {
            return $this->repositoryClass::resolveWith($item)
                ->withExtraFields(
                    collect($this->pivotFields)->each(function (Field $field) use ($item) {
                        return $field->resolveCallback(fn () => $item->pivot->{$field->attribute});
                    })->all()
                )
                ->eagerState();
        });

        return $this;
    }

    public function attachCallback(Closure $callback)
    {
        $this->attachCallback = $callback;

        return $this;
    }

    public function detachCallback(Closure $callback)
    {
        $this->detachCallback = $callback;

        return $this;
    }
}
