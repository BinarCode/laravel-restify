<?php

namespace Binaryk\LaravelRestify\Http\Requests\Concerns;

use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Binaryk\LaravelRestify\Http\Requests\GlobalSearchRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryDestroyRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryIndexRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryShowRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryStoreBulkRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryStoreRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryUpdateBulkRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryUpdateRequest;

trait DetermineRequestType
{
    public function isIndexRequest(): bool
    {
        return $this instanceof RepositoryIndexRequest;
    }

    public function isGlobalRequest(): bool
    {
        return $this instanceof GlobalSearchRequest;
    }

    public function isShowRequest(): bool
    {
        return $this instanceof RepositoryShowRequest;
    }

    public function isUpdateRequest(): bool
    {
        return $this instanceof RepositoryUpdateRequest;
    }

    public function isStoreRequest(): bool
    {
        return $this instanceof RepositoryStoreRequest;
    }

    public function isDestroyRequest(): bool
    {
        return $this instanceof RepositoryDestroyRequest;
    }

    public function isStoreBulkRequest(): bool
    {
        return $this instanceof RepositoryStoreBulkRequest;
    }

    public function isUpdateBulkRequest(): bool
    {
        return $this instanceof RepositoryUpdateBulkRequest;
    }

    public function isActionRequest(): bool
    {
        return $this instanceof ActionRequest;
    }
}
