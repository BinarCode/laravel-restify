<?php

namespace Binaryk\LaravelRestify\MCP\Requests;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class McpRequest extends RestifyRequest implements McpRequestable
{
    /**
     * Get the MCP tool name from the request payload.
     */
    protected function getToolName(): string
    {
        return $this->input('params.name', '');
    }

    public function isIndexRequest(): bool
    {
        return $this instanceof McpIndexRequest;
    }

    public function isShowRequest(): bool
    {
        return $this instanceof McpShowRequest;
    }

    public function isStoreRequest(): bool
    {
        return $this instanceof McpStoreRequest;
    }

    public function isUpdateRequest(): bool
    {
        return $this instanceof McpUpdateRequest;
    }

    public function isDestroyRequest(): bool
    {
        return $this instanceof McpDestroyRequest;
    }

    public function isStoreBulkRequest(): bool
    {
        return $this instanceof McpStoreBulkRequest;
    }

    public function isUpdateBulkRequest(): bool
    {
        return $this instanceof McpUpdateBulkRequest;
    }

    public function isActionRequest(): bool
    {
        return $this instanceof McpActionRequest;
    }

    public function isGetterRequest(): bool
    {
        return $this instanceof McpGetterRequest;
    }

    public function isGlobalRequest(): bool
    {
        // MCP doesn't handle global search requests
        return false;
    }
}
