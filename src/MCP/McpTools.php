<?php

namespace Binaryk\LaravelRestify\MCP;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for MCP tools discovery and management.
 *
 * @method static \Illuminate\Support\Collection all()
 * @method static \Illuminate\Support\Collection category(string $category)
 * @method static \Illuminate\Support\Collection repository(string $repositoryKey)
 * @method static array|null find(string $name)
 * @method static void register(array $tools)
 * @method static bool canUse(string|object $tool)
 * @method static \Illuminate\Support\Collection authorized()
 * @method static void setServer(\Binaryk\LaravelRestify\MCP\RestifyServer $server)
 * @method static \Binaryk\LaravelRestify\MCP\RestifyServer|null server()
 * @method static void clear()
 * @method static \Illuminate\Support\Collection byCategory()
 * @method static \Illuminate\Support\Collection byRepository()
 * @method static void rediscover()
 * @method static \Illuminate\Support\Collection getAvailableRepositories(?string $search = null)
 * @method static array getRepositoryOperations(string $repositoryKey)
 * @method static array getOperationDetails(string $repositoryKey, string $operationType, ?string $operationName = null)
 * @method static \Laravel\Mcp\Response executeOperation(string $repositoryKey, string $operationType, ?string $operationName, array $parameters)
 *
 * @see \Binaryk\LaravelRestify\MCP\McpToolsManager
 */
class McpTools extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return McpToolsManager::class;
    }
}
