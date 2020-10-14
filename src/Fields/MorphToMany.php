<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Repositories\Repository;

class MorphToMany extends HasField
{
    /**
     * The pivot table columns to retrieve.
     *
     * @var array
     */
    protected $pivotFields = [];

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

    /**
     * Set the columns on the pivot table to retrieve.
     *
     * @param array|mixed $fields
     * @return $this
     */
    public function withPivot($fields)
    {
        $this->pivotFields = array_merge(
            $this->pivotFields, is_array($fields) ? $fields : func_get_args()
        );

        return $this;
    }
}
