<?php

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Laravel\Mcp\Server\McpServiceProvider;

class ActionToMcpInvocationDescriptorTest extends IntegrationTestCase
{
    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            McpServiceProvider::class,
        ]);
    }

    protected function tearDown(): void
    {
        Restify::$repositories = [];

        parent::tearDown();
    }

    public function test_to_mcp_invocation_descriptor_builds_correct_descriptor(): void
    {
        $action = new class extends Action
        {
            public static string $uriKey = 'sample-action';

            public function rules(): array
            {
                return [
                    'campaign_id' => ['required', 'string'],
                    'lead_stage_id' => ['required', 'integer'],
                    'context' => ['nullable', 'string'],
                ];
            }

            public function description(RestifyRequest $request): string
            {
                return 'Sample action description.';
            }

            public function handle(ActionRequest $request, Collection $models): JsonResponse
            {
                return response()->json(['success' => true]);
            }
        };

        $repository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'samples';

            public function actions(RestifyRequest $request): array
            {
                return [];
            }

            public function mcpAllowsActions(): bool
            {
                return true;
            }
        };

        Restify::repositories([
            $repository::class,
        ]);

        $descriptor = $action->toMcpInvocationDescriptor($repository, bind: [
            'campaign_id' => '01krb179j1ncmjpbggah5gcwvb',
        ]);

        $this->assertSame('samples-sample-action-action-tool', $descriptor->toolName);
        $this->assertSame('samples', $descriptor->repositoryUriKey);
        $this->assertSame('sample-action', $descriptor->actionUriKey);
        $this->assertSame('Sample action description.', $descriptor->description);
        $this->assertSame('01krb179j1ncmjpbggah5gcwvb', $descriptor->examplePayload['campaign_id']);
        $this->assertSame('<integer, required>', $descriptor->examplePayload['lead_stage_id']);
        $this->assertSame('<string, optional>', $descriptor->examplePayload['context']);
    }
}
