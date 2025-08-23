<?php

namespace Binaryk\LaravelRestify\Http\Requests\Concerns;

use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Binaryk\LaravelRestify\Http\Requests\GetterRequest;
use Binaryk\LaravelRestify\Http\Requests\GlobalSearchRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryDestroyRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryIndexRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryShowRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryStoreBulkRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryStoreRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryUpdateBulkRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryUpdateRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpRequest;

/**
 * @mixin RestifyRequest
 */
trait DetermineRequestType
{
    public function isIndexRequest(): bool
    {
        if ($this instanceof McpRequest) {
            return $this->isIndexRequest();
        }
        
        return $this instanceof RepositoryIndexRequest;
    }

    public function isGlobalRequest(): bool
    {
        return $this instanceof GlobalSearchRequest;
    }

    public function isShowRequest(): bool
    {
        if ($this instanceof McpRequest) {
            return $this->isShowRequest();
        }
        
        return $this instanceof RepositoryShowRequest;
    }

    public function isUpdateRequest(): bool
    {
        if ($this instanceof McpRequest) {
            return $this->isUpdateRequest();
        }
        
        return $this instanceof RepositoryUpdateRequest;
    }

    public function isStoreRequest(): bool
    {
        if ($this instanceof McpRequest) {
            return $this->isStoreRequest();
        }
        
        return $this instanceof RepositoryStoreRequest;
    }

    public function isDestroyRequest(): bool
    {
        if ($this instanceof McpRequest) {
            return $this->isDestroyRequest();
        }
        
        return $this instanceof RepositoryDestroyRequest;
    }

    public function isStoreBulkRequest(): bool
    {
        if ($this instanceof McpRequest) {
            return $this->isStoreBulkRequest();
        }
        
        return $this instanceof RepositoryStoreBulkRequest;
    }

    public function isUpdateBulkRequest(): bool
    {
        if ($this instanceof McpRequest) {
            return $this->isUpdateBulkRequest();
        }
        
        return $this instanceof RepositoryUpdateBulkRequest;
    }

    public function isActionRequest(): bool
    {
        if ($this instanceof McpRequest) {
            return $this->isActionRequest();
        }
        
        return $this instanceof ActionRequest;
    }

    public function isGetterRequest(): bool
    {
        if ($this instanceof McpRequest) {
            return $this->isGetterRequest();
        }
        
        return $this instanceof GetterRequest;
    }
}
