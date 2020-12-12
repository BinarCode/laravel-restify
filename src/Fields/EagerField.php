<?php

namespace Binaryk\LaravelRestify\Fields;

use Illuminate\Auth\Access\AuthorizationException;
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
     * @var string
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
            $class = get_class($relatedModel);
            $policy = get_class(Gate::getPolicyFor($relatedModel));

            abort(403, "You are not authorized to see the [{$class}] relationship from the BelongsTo field from the BelongsTo field. Check the [show] method from the [$policy]");
        }

        return $this;
    }
}
