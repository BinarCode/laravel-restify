<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Laravel\Mcp\Server\Tools\ToolInputSchema;

/**
 * @mixin \Binaryk\LaravelRestify\Repositories\Repository
 */
trait McpStoreTool
{
    public function storeTool(array $arguments, McpRequest $request): array
    {
        $request->merge($arguments);
        $this->sanitizeToolRequest($request, $arguments);

        return $this->store($request);
    }

    public static function storeToolSchema(ToolInputSchema $schema): void
    {
        $repository = static::resolveWith(static::newModel());

        $repository->collectFields($request = app(McpRequest::class))
            ->forStore($request, $repository)
            ->withoutActions($request, $repository)
            ->each(function (Field $field) use ($schema, $repository) {
                $field->resolveToolSchema($schema, $repository);
            });
    }
}
