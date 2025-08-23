<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\MCP;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Getters\Getter;
use Binaryk\LaravelRestify\MCP\Concerns\McpTools;
use Binaryk\LaravelRestify\MCP\Requests\McpActionRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpGetterRequest;
use Binaryk\LaravelRestify\MCP\Resources\ApplicationInfo;
use Binaryk\LaravelRestify\MCP\Tools\Operations\ActionTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\DeleteTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\GetterTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\IndexTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\ShowTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\StoreTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\UpdateTool;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Laravel\Mcp\Server;

class RestifyServer extends Server
{
    public string $serverName = 'Laravel Restify';

    public string $serverVersion = '0.0.1';

    public string $instructions = 'Laravel Restify MCP server providing access to RESTful API resources, repository operations, field management, action execution, and filter/search capabilities. Restify helps build and interact with REST APIs efficiently.';

    public int $defaultPaginationLength = 50;

    /**
     * @var string[]
     */
    public array $resources = [
        ApplicationInfo::class,
    ];

    public function boot(): void
    {
        $this->discoverTools();
        $this->discoverRepositoryTools();
        $this->discoverResources();
        $this->discoverPrompts();
    }

    /**
     * @return array<string>
     */
    protected function discoverTools(): array
    {
        $excludedTools = config('restify.mcp.tools.exclude', []);
        $toolDir = new \DirectoryIterator(__DIR__.DIRECTORY_SEPARATOR.'Tools');

        foreach ($toolDir as $toolFile) {
            if ($toolFile->isFile() && $toolFile->getExtension() === 'php') {
                $fqdn = 'Binaryk\\LaravelRestify\\MCP\\Tools\\'.$toolFile->getBasename('.php');
                if (class_exists($fqdn) && ! in_array($fqdn, $excludedTools, true)) {
                    $this->addTool($fqdn);
                }
            }
        }

        $extraTools = config('restify.mcp.tools.include', []);
        foreach ($extraTools as $toolClass) {
            if (class_exists($toolClass)) {
                $this->addTool($toolClass);
            }
        }

        return $this->registeredTools;
    }

    protected function discoverRepositoryTools(): void
    {
        collect(Restify::$repositories)
            ->filter(function (string $repository) {
                return in_array(McpTools::class, class_uses_recursive($repository));
            })
            ->each(function (string $repository) {
                $repositoryInstance = app($repository);

                if (method_exists($repositoryInstance, 'mcpAllowsIndex') && $repositoryInstance->mcpAllowsIndex()) {
                    $this->addTool(new IndexTool($repository));
                }

                if (method_exists($repositoryInstance, 'mcpAllowsShow') && $repositoryInstance->mcpAllowsShow()) {
                    $this->addTool(new ShowTool($repository));
                }

                if (method_exists($repositoryInstance, 'mcpAllowsStore') && $repositoryInstance->mcpAllowsStore()) {
                    $this->addTool(new StoreTool($repository));
                }

                if (method_exists($repositoryInstance, 'mcpAllowsUpdate') && $repositoryInstance->mcpAllowsUpdate()) {
                    $this->addTool(new UpdateTool($repository));
                }

                if (method_exists($repositoryInstance, 'mcpAllowsDelete') && $repositoryInstance->mcpAllowsDelete()) {
                    $this->addTool(new DeleteTool($repository));
                }

                if (method_exists($repositoryInstance, 'mcpAllowsActions') && $repositoryInstance->mcpAllowsActions()) {
                    $this->discoverActionsForRepository($repository, $repositoryInstance);
                }

                if (method_exists($repositoryInstance, 'mcpAllowsGetters') && $repositoryInstance->mcpAllowsGetters()) {
                    $this->discoverGettersForRepository($repository, $repositoryInstance);
                }
            });
    }

    protected function discoverActionsForRepository(string $repositoryClass, Repository $repositoryInstance): void
    {
        $actionRequest = app(McpActionRequest::class);
        $actionRequest->merge([
            'mcp_repository_key' => $repositoryInstance::uriKey(),
        ]);

        $repositoryInstance->resolveActions($actionRequest)
            ->filter(fn ($action) => $action instanceof Action)
            ->filter(fn (Action $action) => $action->isShownOnMcp($actionRequest, $repositoryInstance))
            ->filter(fn (Action $action) => $action->authorizedToSee($actionRequest))
            ->unique(fn (Action $action) => $action->uriKey()) // Avoid duplicates
            ->each(fn (Action $action) => $this->addTool(new ActionTool($repositoryClass, $action)));
    }

    protected function discoverGettersForRepository(string $repositoryClass, Repository $repositoryInstance): void
    {
        $getterRequest = app(McpGetterRequest::class);
        $getterRequest->merge([
            'mcp_repository_key' => $repositoryInstance::uriKey(),
        ]);

        $repositoryInstance->resolveGetters($getterRequest)
            ->filter(fn ($getter) => $getter instanceof Getter)
            ->filter(fn (Getter $getter) => $getter->isShownOnMcp($getterRequest, $repositoryInstance))
            ->filter(fn (Getter $getter) => $getter->authorizedToSee($getterRequest))
            ->unique(fn (Getter $getter) => $getter->uriKey()) // Avoid duplicates
            ->each(fn (Getter $getter) => $this->addTool(new GetterTool($repositoryClass, $getter)));
    }

    /**
     * @return array<string>
     */
    protected function discoverResources(): array
    {
        $excludedResources = config('restify.mcp.resources.exclude', []);
        $resourceDir = new \DirectoryIterator(__DIR__.DIRECTORY_SEPARATOR.'Resources');
        foreach ($resourceDir as $resourceFile) {
            if ($resourceFile->isFile() && $resourceFile->getExtension() === 'php') {
                $fqdn = 'Binaryk\\LaravelRestify\\MCP\\Resources\\'.$resourceFile->getBasename('.php');
                if (class_exists($fqdn) && ! in_array($fqdn, $excludedResources, true)) {
                    $this->addResource($fqdn);
                }
            }
        }

        $extraResources = config('restify.mcp.resources.include', []);
        foreach ($extraResources as $resourceClass) {
            if (class_exists($resourceClass)) {
                $this->addResource($resourceClass);
            }
        }

        return $this->registeredResources;
    }

    /**
     * @return array<string>
     */
    protected function discoverPrompts(): array
    {
        $excludedPrompts = config('restify.mcp.prompts.exclude', []);
        $promptDir = new \DirectoryIterator(__DIR__.DIRECTORY_SEPARATOR.'Prompts');
        foreach ($promptDir as $promptFile) {
            if ($promptFile->isFile() && $promptFile->getExtension() === 'php') {
                $fqdn = 'Binaryk\\LaravelRestify\\MCP\\Prompts\\'.$promptFile->getBasename('.php');
                if (class_exists($fqdn) && ! in_array($fqdn, $excludedPrompts, true)) {
                    $this->addPrompt($fqdn);
                }
            }
        }

        $extraPrompts = config('restify.mcp.prompts.include', []);
        foreach ($extraPrompts as $promptClass) {
            if (class_exists($promptClass)) {
                $this->addPrompt($promptClass);
            }
        }

        return $this->registeredPrompts;
    }
}
