<?php

namespace Binaryk\LaravelRestify\Requests;

use Binaryk\LaravelRestify\Restify;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RestifyRequest extends FormRequest
{
    /**
     * Get the class name of the resource being requested.
     *
     * @return mixed
     */
    public function resource()
    {
        return tap(Restify::resourceForKey($this->route('resource')), function ($resource) {
            abort_if(is_null($resource), 404);
            abort_if(! $resource::authorizedToViewAny($this), 403);
        });
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
