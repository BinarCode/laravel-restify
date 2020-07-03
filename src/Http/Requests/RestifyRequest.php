<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Http\Controllers\RepositoryUpdateBulkController;
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
     * Determine if the request is on repository index e.g. restify-api/users.
     *
     * @return bool
     */
    public function isIndexRequest()
    {
        return $this instanceof RepositoryIndexRequest;
    }

    /**
     * Determine if the request is on repository detail e.g. restify-api/users/1
     * This will match any verbs (PATCH, DELETE or GET).
     * @return bool
     */
    public function isShowRequest()
    {
        return $this instanceof RepositoryShowRequest;
    }

    public function isUpdateRequest()
    {
        return $this instanceof RepositoryUpdateRequest;
    }

    public function isStoreRequest()
    {
        return $this instanceof RepositoryStoreRequest;
    }

    public function isStoreBulkRequest()
    {
        return $this instanceof RepositoryStoreBulkRequest;
    }

    public function isUpdateBulkRequest()
    {
        return $this instanceof RepositoryUpdateBulkRequest;
    }

    public function isViaRepository()
    {
        return $this->viaRepository && $this->viaRepositoryId;
    }
}
