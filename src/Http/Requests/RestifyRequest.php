<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Restify;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RestifyRequest extends FormRequest
{
    use InteractWithRepositories;

    /**
     * @return bool
     */
    public function isProduction()
    {
        return App::environment('production');
    }

    /**
     * @return bool
     */
    public function isDev()
    {
        return false === $this->isProduction();
    }

    /**
     * Determine if the request is on repository index e.g. restify-api/users
     *
     * @return bool
     */
    public function isIndexRequest()
    {
        $path = trim(Restify::path($this->route('repository')), '/') ?: '/';

        return $this->is($path);
    }

    /**
     * Determine if the request is on repository detail e.g. restify-api/users/1
     * This will match any verbs (PATCH, DELETE or GET)
     * @return bool
     */
    public function isDetailRequest()
    {
        return ! $this->isIndexRequest();
    }
}
