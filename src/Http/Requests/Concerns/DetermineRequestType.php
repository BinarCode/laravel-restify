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
use Binaryk\LaravelRestify\MCP\Requests\McpActionRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpDestroyRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpGetterRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpIndexRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpShowRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpStoreBulkRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpStoreRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpUpdateBulkRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpUpdateRequest;

/**
 * @mixin RestifyRequest
 */
trait DetermineRequestType
{
    public function isIndexRequest(): bool
    {
        return $this instanceof RepositoryIndexRequest
            || $this instanceof McpIndexRequest;
    }

    public function isGlobalRequest(): bool
    {
        return $this instanceof GlobalSearchRequest;
    }

    public function isShowRequest(): bool
    {
        return $this instanceof RepositoryShowRequest
            || $this instanceof McpShowRequest;
    }

    public function isUpdateRequest(): bool
    {
        return $this instanceof RepositoryUpdateRequest
            || $this instanceof McpUpdateRequest;
    }

    public function isStoreRequest(): bool
    {
        return $this instanceof RepositoryStoreRequest
            || $this instanceof McpStoreRequest;
    }

    public function isDestroyRequest(): bool
    {
        return $this instanceof RepositoryDestroyRequest
            || $this instanceof McpDestroyRequest;
    }

    public function isStoreBulkRequest(): bool
    {
        return $this instanceof RepositoryStoreBulkRequest
            || $this instanceof McpStoreBulkRequest;
    }

    public function isUpdateBulkRequest(): bool
    {
        return $this instanceof RepositoryUpdateBulkRequest
            || $this instanceof McpUpdateBulkRequest;
    }

    public function isActionRequest(): bool
    {
        return $this instanceof ActionRequest
            || $this instanceof McpActionRequest;
    }

    public function isGetterRequest(): bool
    {
        return $this instanceof GetterRequest
            || $this instanceof McpGetterRequest;
    }
}
