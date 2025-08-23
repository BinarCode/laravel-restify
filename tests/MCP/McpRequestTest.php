<?php

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

class McpRequestTest extends IntegrationTestCase
{
    public function test_mcp_request_detects_index_tool(): void
    {
        $request = new McpRequest([
            'params' => [
                'name' => 'users-index-tool'
            ]
        ]);

        $this->assertTrue($request->isIndexRequest());
        $this->assertFalse($request->isShowRequest());
        $this->assertFalse($request->isStoreRequest());
    }

    public function test_mcp_request_detects_show_tool(): void
    {
        $request = new McpRequest([
            'params' => [
                'name' => 'posts-show-tool'
            ]
        ]);

        $this->assertTrue($request->isShowRequest());
        $this->assertFalse($request->isIndexRequest());
        $this->assertFalse($request->isUpdateRequest());
    }

    public function test_mcp_request_detects_store_tool(): void
    {
        $request = new McpRequest([
            'params' => [
                'name' => 'orders-store-tool'
            ]
        ]);

        $this->assertTrue($request->isStoreRequest());
        $this->assertFalse($request->isShowRequest());
        $this->assertFalse($request->isUpdateRequest());
    }

    public function test_mcp_request_detects_update_tool(): void
    {
        $request = new McpRequest([
            'params' => [
                'name' => 'comments-update-tool'
            ]
        ]);

        $this->assertTrue($request->isUpdateRequest());
        $this->assertFalse($request->isStoreRequest());
        $this->assertFalse($request->isDestroyRequest());
    }

    public function test_mcp_request_detects_destroy_tools(): void
    {
        $deleteRequest = new McpRequest([
            'params' => [
                'name' => 'comments-delete-tool'
            ]
        ]);

        $destroyRequest = new McpRequest([
            'params' => [
                'name' => 'users-destroy-tool'
            ]
        ]);

        $this->assertTrue($deleteRequest->isDestroyRequest());
        $this->assertTrue($destroyRequest->isDestroyRequest());
        $this->assertFalse($deleteRequest->isUpdateRequest());
    }

    public function test_mcp_request_detects_bulk_tools(): void
    {
        $storeBulkRequest = new McpRequest([
            'params' => [
                'name' => 'users-store-bulk-tool'
            ]
        ]);

        $updateBulkRequest = new McpRequest([
            'params' => [
                'name' => 'posts-update-bulk-tool'
            ]
        ]);

        $this->assertTrue($storeBulkRequest->isStoreBulkRequest());
        $this->assertTrue($updateBulkRequest->isUpdateBulkRequest());
        $this->assertFalse($storeBulkRequest->isStoreRequest()); // Should be bulk, not regular
    }

    public function test_mcp_request_detects_getter_tool(): void
    {
        $request = new McpRequest([
            'params' => [
                'name' => 'analytics-getter-tool'
            ]
        ]);

        $this->assertTrue($request->isGetterRequest());
        $this->assertFalse($request->isIndexRequest());
        $this->assertFalse($request->isActionRequest());
    }

    public function test_mcp_request_detects_action_tool(): void
    {
        $request = new McpRequest([
            'params' => [
                'name' => 'users-action-tool'
            ]
        ]);

        $this->assertTrue($request->isActionRequest());
        $this->assertFalse($request->isGetterRequest());
        $this->assertFalse($request->isIndexRequest());
    }

    public function test_mcp_request_global_request_always_false(): void
    {
        $request = new McpRequest([
            'params' => [
                'name' => 'global-search-tool'
            ]
        ]);

        $this->assertFalse($request->isGlobalRequest());
    }

    public function test_mcp_request_with_no_tool_name(): void
    {
        $request = new McpRequest([]);

        $this->assertFalse($request->isIndexRequest());
        $this->assertFalse($request->isShowRequest());
        $this->assertFalse($request->isStoreRequest());
        $this->assertFalse($request->isGetterRequest());
    }
}