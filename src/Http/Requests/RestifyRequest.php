<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\UnauthorizedException;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RestifyRequest extends FormRequest
{
    /**
     * Get the class name of the repository being requested.
     *
     * @return mixed
     */
    public function repository()
    {
        return tap(Restify::repositoryForKey($this->route('repository')), function ($repository) {
            if (is_null($repository)) {
                throw new EntityNotFoundException(__('Repository :name not found.', [
                    'name' => $repository,
                ]), 404);
            }

            if (! $repository::authorizedToViewAny($this)) {
                throw new UnauthorizedException(__('Unauthorized to view repository :name.', [
                    'name' => $repository,
                ]), 403);
            }
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
