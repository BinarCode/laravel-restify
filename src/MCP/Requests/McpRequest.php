<?php

namespace Binaryk\LaravelRestify\MCP\Requests;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class McpRequest extends RestifyRequest
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
        return str_contains($this->getToolName(), '-index-tool');
    }

    public function isShowRequest(): bool
    {
        return str_contains($this->getToolName(), '-show-tool');
    }

    public function isStoreRequest(): bool
    {
        return str_contains($this->getToolName(), '-store-tool');
    }

    public function isUpdateRequest(): bool
    {
        return str_contains($this->getToolName(), '-update-tool');
    }

    public function isDestroyRequest(): bool
    {
        $toolName = $this->getToolName();
        return str_contains($toolName, '-delete-tool') || str_contains($toolName, '-destroy-tool');
    }

    public function isStoreBulkRequest(): bool
    {
        return str_contains($this->getToolName(), '-store-bulk-tool');
    }

    public function isUpdateBulkRequest(): bool
    {
        return str_contains($this->getToolName(), '-update-bulk-tool');
    }

    public function isActionRequest(): bool
    {
        return str_contains($this->getToolName(), '-action-tool');
    }

    public function isGetterRequest(): bool
    {
        return str_contains($this->getToolName(), '-getter-tool');
    }

    public function isGlobalRequest(): bool
    {
        // MCP doesn't handle global search requests
        return false;
    }
}
