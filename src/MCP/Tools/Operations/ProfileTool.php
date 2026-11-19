<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\MCP\Requests\McpIndexRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Arr;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ProfileTool extends Tool
{
    /**
     * The arguments forwarded to the repository: the relationships to load, under either name.
     *
     * @var list<string>
     */
    private const ARGUMENTS = ['include', 'related'];

    public function __construct(protected string $repositoryClass) {}

    public function repository(): Repository
    {
        return app($this->repositoryClass);
    }

    public function title(): string
    {
        return 'Profile Tool';
    }

    public function name(): string
    {
        $uriKey = $this->repositoryClass::uriKey();

        return "{$uriKey}-profile-tool";
    }

    public function description(): string
    {
        $modelName = class_basename($this->repositoryClass::guessModelClassName());

        return "Get the current authenticated user profile including {$modelName} and relationship information.";
    }

    public function schema(JsonSchema $schema): array
    {
        $relatedOptions = $this->repositoryClass::collectRelated()
            ->intoAssoc()
            ->keys()
            ->toArray();

        return [
            'include' => $schema->string()->description('Comma-separated list of relationships to include in the response. Available options: '.implode(', ',
                $relatedOptions).' (e.g., include=employee,roles.permissions)'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = auth()->user();

        if (! $user) {
            return Response::error(json_encode([
                'error' => 'No authenticated user found',
                'code' => 'UNAUTHENTICATED',
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        }

        $repository = $this->repository();

        $mcpRequest = app(McpIndexRequest::class)->forRepository($repository::uriKey());

        // Only the arguments of this tool reach the repository. Whatever else a caller sends, as an argument or on
        // the URL of the MCP endpoint, is dropped: a filter, search, sort or page must not decide whose row is read.
        foreach (array_diff($mcpRequest->query->keys(), self::ARGUMENTS) as $key) {
            $mcpRequest->query->remove($key);
        }

        $mcpRequest->replace(Arr::only($request->all(), self::ARGUMENTS));

        $repository->request = $mcpRequest;

        return Response::structured($repository->profileTool($mcpRequest, $user));
    }
}
