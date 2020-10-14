<?php

namespace Binaryk\LaravelRestify\Fields;

use Illuminate\Http\Request;

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
}
