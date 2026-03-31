<?php

namespace Binaryk\LaravelRestify\Tests\Fields;

use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Fields\FieldCollection;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpIndexRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpShowRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpStoreRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpUpdateRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

class FieldMcpVisibilityTest extends IntegrationTestCase
{
    protected PostRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PostRepository;
    }

    public function test_field_collection_filters_mcp_hidden_fields_for_index(): void
    {
        $fields = new FieldCollection([
            Field::make('title'), // Visible in both
            Field::make('secret_token')->hideFromMcp(), // Hidden in MCP
            Field::make('mcp_only')->showOnIndex(false)->showOnMcp(true), // Only in MCP
            Field::make('admin_notes')->hideFromMcp(function ($request) {
                return ! $request->get('is_admin', false);
            }), // Conditional MCP visibility
        ]);

        //        // Regular request
        $regularRequest = new RestifyRequest;
        $regularIndexFields = $fields->forIndex($regularRequest, $this->repository);

        $regularFieldNames = $regularIndexFields->map(fn ($field) => $field->getAttribute())->toArray();
        $this->assertContains('title', $regularFieldNames);
        $this->assertContains('secret_token', $regularFieldNames);
        $this->assertNotContains('mcp_only', $regularFieldNames); // Hidden from regular index
        $this->assertContains('admin_notes', $regularFieldNames);

        // MCP request without admin
        $mcpRequest = new McpIndexRequest;
        $mcpIndexFields = $fields->forIndex($mcpRequest, $this->repository);

        $mcpFieldNames = $mcpIndexFields->map(fn ($field) => $field->getAttribute())->toArray();
        $this->assertContains('title', $mcpFieldNames);
        $this->assertNotContains('secret_token', $mcpFieldNames); // Hidden from MCP
        $this->assertContains('mcp_only', $mcpFieldNames); // Visible in MCP
        $this->assertNotContains('admin_notes', $mcpFieldNames); // Hidden due to callback
    }

    public function test_field_collection_filters_mcp_hidden_fields_for_show(): void
    {
        $fields = new FieldCollection([
            Field::make('title'),
            Field::make('secret_key')->hideFromMcp(),
            Field::make('internal_id')->showOnShow(false)->showOnMcp(true),
        ]);

        // Regular show request
        $regularRequest = new RestifyRequest;
        $regularShowFields = $fields->forShow($regularRequest, $this->repository);

        $regularFieldNames = $regularShowFields->map(fn ($field) => $field->getAttribute())->toArray();
        $this->assertContains('title', $regularFieldNames);
        $this->assertContains('secret_key', $regularFieldNames);
        $this->assertNotContains('internal_id', $regularFieldNames);

        // MCP show request
        $mcpRequest = new McpRequest;
        $mcpShowFields = $fields->forShow($mcpRequest, $this->repository);

        $mcpFieldNames = $mcpShowFields->map(fn ($field) => $field->getAttribute())->toArray();
        $this->assertContains('title', $mcpFieldNames);
        $this->assertNotContains('secret_key', $mcpFieldNames); // Hidden from MCP
        $this->assertContains('internal_id', $mcpFieldNames); // Visible in MCP
    }

    public function test_mcp_visibility_with_user_permissions(): void
    {
        $fields = new FieldCollection([
            Field::make('public_data'),
            Field::make('admin_data')->hideFromMcp(function ($request) {
                return ! $request->get('is_admin', false);
            }),
            Field::make('user_data')->showOnMcp(function ($request) {
                return $request->get('can_view_data', false);
            }),
        ]);

        // MCP request with admin permissions
        $mcpRequestWithAdmin = new McpRequest(['is_admin' => true, 'can_view_data' => true]);

        $adminFields = $fields->forIndex($mcpRequestWithAdmin, $this->repository);
        $adminFieldNames = $adminFields->map(fn ($field) => $field->getAttribute())->toArray();

        $this->assertContains('public_data', $adminFieldNames);
        $this->assertContains('admin_data', $adminFieldNames); // Visible to admin
        $this->assertContains('user_data', $adminFieldNames); // Visible with permission

        // MCP request with regular user permissions
        $mcpRequestWithUser = new McpRequest(['is_admin' => false, 'can_view_data' => false]);

        $userFields = $fields->forIndex($mcpRequestWithUser, $this->repository);
        $userFieldNames = $userFields->map(fn ($field) => $field->getAttribute())->toArray();

        $this->assertContains('public_data', $userFieldNames);
        $this->assertNotContains('admin_data', $userFieldNames); // Hidden from non-admin
        $this->assertNotContains('user_data', $userFieldNames); // Hidden without permission
    }

    public function test_mcp_visibility_respects_general_hidden_fields(): void
    {
        $fields = new FieldCollection([
            Field::make('visible_field'),
            Field::make('generally_hidden')->hidden(true),
            Field::make('mcp_hidden')->hideFromMcp(),
            Field::make('both_hidden')->hidden(true)->hideFromMcp(),
        ]);

        $mcpRequest = new McpRequest;
        $mcpFields = $fields->forIndex($mcpRequest, $this->repository);
        $mcpFieldNames = $mcpFields->map(fn ($field) => $field->getAttribute())->toArray();

        $this->assertContains('visible_field', $mcpFieldNames);
        $this->assertNotContains('generally_hidden', $mcpFieldNames); // Hidden by general rule
        $this->assertNotContains('mcp_hidden', $mcpFieldNames); // Hidden by MCP rule
        $this->assertNotContains('both_hidden', $mcpFieldNames); // Hidden by both rules
    }

    public function test_mcp_field_count_matches_expected(): void
    {
        $fields = new FieldCollection([
            Field::make('field1'),
            Field::make('field2')->hideFromMcp(),
            Field::make('field3'),
            Field::make('field4')->hideFromMcp(),
            Field::make('field5')->showOnMcp(true),
        ]);

        // Regular request should show all except those explicitly hidden from index
        $regularRequest = new RestifyRequest;
        $regularFields = $fields->forIndex($regularRequest, $this->repository);
        $this->assertCount(5, $regularFields); // All visible in regular request

        // MCP request should filter out hideFromMcp fields
        $mcpRequest = new McpRequest;
        $mcpFields = $fields->forIndex($mcpRequest, $this->repository);
        $this->assertCount(3, $mcpFields); // field1, field3, field5 visible in MCP
    }

    public function test_field_collection_preserves_other_filters_with_mcp(): void
    {
        $fields = new FieldCollection([
            Field::make('normal_field'),
            Field::make('readonly_field')->readonly(),
            Field::make('mcp_hidden')->hideFromMcp(),
            new EagerField('eager_field', 'relation'),
        ]);

        $mcpRequest = new McpRequest;

        // forIndex should exclude EagerFields and apply MCP visibility
        $indexFields = $fields->forIndex($mcpRequest, $this->repository);
        $indexFieldNames = $indexFields->map(fn ($field) => $field->getAttribute())->toArray();

        $this->assertContains('normal_field', $indexFieldNames);
        $this->assertContains('readonly_field', $indexFieldNames);
        $this->assertNotContains('mcp_hidden', $indexFieldNames);
        $this->assertNotContains('eager_field', $indexFieldNames); // EagerFields excluded from index
    }

    public function test_mcp_show_request_does_not_cause_infinite_loop(): void
    {
        $fields = new FieldCollection([
            Field::make('title'),
            Field::make('secret_key')->hideFromMcp(),
            Field::make('visible_field')->showOnShow(true),
        ]);

        // This test ensures that McpShowRequest doesn't cause infinite recursion
        // between isShownOnMcp() and isShownOnShow() methods
        $mcpShowRequest = new McpShowRequest;

        // This should not cause infinite loop
        $showFields = $fields->forShow($mcpShowRequest, $this->repository);
        $fieldNames = $showFields->map(fn ($field) => $field->getAttribute())->toArray();

        $this->assertContains('title', $fieldNames);
        $this->assertNotContains('secret_key', $fieldNames); // Hidden from MCP
        $this->assertContains('visible_field', $fieldNames);
    }

    public function test_mcp_index_request_does_not_cause_infinite_loop(): void
    {
        $fields = new FieldCollection([
            Field::make('name'),
            Field::make('internal_token')->hideFromMcp(),
            Field::make('public_data')->showOnIndex(true),
        ]);

        // This test ensures that McpIndexRequest doesn't cause infinite recursion
        $mcpIndexRequest = new McpIndexRequest;

        // This should not cause infinite loop
        $indexFields = $fields->forIndex($mcpIndexRequest, $this->repository);
        $fieldNames = $indexFields->map(fn ($field) => $field->getAttribute())->toArray();

        $this->assertContains('name', $fieldNames);
        $this->assertNotContains('internal_token', $fieldNames); // Hidden from MCP
        $this->assertContains('public_data', $fieldNames);
    }

    public function test_mcp_store_request_respects_hide_from_mcp(): void
    {
        $fields = new FieldCollection([
            Field::make('title'),
            Field::make('secret_api_key')->hideFromMcp(),
            Field::make('content'),
            Field::make('internal_metadata')->hideFromMcp(function ($request) {
                return ! $request->get('is_admin', false);
            }),
        ]);

        // MCP store request without admin
        $mcpStoreRequest = new McpStoreRequest;
        $storeFields = $fields->forStore($mcpStoreRequest, $this->repository);
        $fieldNames = $storeFields->map(fn ($field) => $field->getAttribute())->toArray();

        $this->assertContains('title', $fieldNames);
        $this->assertNotContains('secret_api_key', $fieldNames); // Hidden from MCP
        $this->assertContains('content', $fieldNames);
        $this->assertNotContains('internal_metadata', $fieldNames); // Hidden by callback

        // MCP store request with admin
        $mcpStoreRequestAdmin = new McpStoreRequest(['is_admin' => true]);
        $storeFieldsAdmin = $fields->forStore($mcpStoreRequestAdmin, $this->repository);
        $fieldNamesAdmin = $storeFieldsAdmin->map(fn ($field) => $field->getAttribute())->toArray();

        $this->assertContains('title', $fieldNamesAdmin);
        $this->assertNotContains('secret_api_key', $fieldNamesAdmin); // Still hidden from MCP
        $this->assertContains('content', $fieldNamesAdmin);
        $this->assertContains('internal_metadata', $fieldNamesAdmin); // Visible to admin

        // Regular store request should show all fields except readonly
        $regularStoreRequest = new RestifyRequest;
        $regularStoreFields = $fields->forStore($regularStoreRequest, $this->repository);
        $regularFieldNames = $regularStoreFields->map(fn ($field) => $field->getAttribute())->toArray();

        $this->assertContains('title', $regularFieldNames);
        $this->assertContains('secret_api_key', $regularFieldNames); // Visible in regular request
        $this->assertContains('content', $regularFieldNames);
        $this->assertContains('internal_metadata', $regularFieldNames);
    }

    public function test_mcp_update_request_respects_hide_from_mcp(): void
    {
        $fields = new FieldCollection([
            Field::make('name'),
            Field::make('password')->hideFromMcp(),
            Field::make('email'),
            Field::make('admin_notes')->hideFromMcp(function ($request) {
                return $request->get('role') !== 'admin';
            }),
        ]);

        // MCP update request without admin role
        $mcpUpdateRequest = new McpUpdateRequest(['role' => 'user']);
        $updateFields = $fields->forUpdate($mcpUpdateRequest, $this->repository);
        $fieldNames = $updateFields->map(fn ($field) => $field->getAttribute())->toArray();

        $this->assertContains('name', $fieldNames);
        $this->assertNotContains('password', $fieldNames); // Hidden from MCP
        $this->assertContains('email', $fieldNames);
        $this->assertNotContains('admin_notes', $fieldNames); // Hidden by callback

        // MCP update request with admin role
        $mcpUpdateRequestAdmin = new McpUpdateRequest(['role' => 'admin']);
        $updateFieldsAdmin = $fields->forUpdate($mcpUpdateRequestAdmin, $this->repository);
        $fieldNamesAdmin = $updateFieldsAdmin->map(fn ($field) => $field->getAttribute())->toArray();

        $this->assertContains('name', $fieldNamesAdmin);
        $this->assertNotContains('password', $fieldNamesAdmin); // Still hidden from MCP
        $this->assertContains('email', $fieldNamesAdmin);
        $this->assertContains('admin_notes', $fieldNamesAdmin); // Visible to admin

        // Regular update request should show all fields except readonly
        $regularUpdateRequest = new RestifyRequest;
        $regularUpdateFields = $fields->forUpdate($regularUpdateRequest, $this->repository);
        $regularFieldNames = $regularUpdateFields->map(fn ($field) => $field->getAttribute())->toArray();

        $this->assertContains('name', $regularFieldNames);
        $this->assertContains('password', $regularFieldNames); // Visible in regular request
        $this->assertContains('email', $regularFieldNames);
        $this->assertContains('admin_notes', $regularFieldNames);
    }
}
