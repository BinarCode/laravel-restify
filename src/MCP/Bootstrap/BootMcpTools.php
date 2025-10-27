<?php

namespace Binaryk\LaravelRestify\MCP\Bootstrap;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Getters\Getter;
use Binaryk\LaravelRestify\MCP\Collections\ToolsCollection;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Enums\OperationTypeEnum;
use Binaryk\LaravelRestify\MCP\Enums\ToolsCategoryEnum;
use Binaryk\LaravelRestify\MCP\Requests\McpActionRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpGetterRequest;
use Binaryk\LaravelRestify\MCP\Tools\Operations\ActionTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\DeleteTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\GetterTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\IndexTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\ProfileTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\ShowTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\StoreTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\UpdateTool;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

/**
 * Bootstrap MCP tools discovery.
 * Contains all the heavy lifting for discovering tools from various sources.
 */
class BootMcpTools
{
    /**
     * Bootstrap and discover all MCP tools.
     */
    public function boot(): array
    {
        // In tests, skip caching to avoid database cache table issues
        if (app()->environment('testing')) {
            return collect()
                ->merge($this->discoverCustomTools())
                ->merge($this->discoverRepositoryTools())
                ->values()
                ->toArray();
        }

        // Cache key includes mode to prevent cache pollution between modes
        $mode = config('restify.mcp.mode', 'direct');
        $cacheKey = "restify.mcp.all_tools_metadata.{$mode}";

        if (App::hasDebugModeEnabled()) {
            return collect()
                ->merge($this->discoverCustomTools())
                ->merge($this->discoverRepositoryTools())
                ->values()
                ->toArray();
        }

        $tools = Cache::remember($cacheKey, 3600, function (): array {
            return collect()
                ->merge($this->discoverCustomTools())
                ->merge($this->discoverRepositoryTools())
                ->values()
                ->toArray();
        });

        return $tools;
    }

    /**
     * Discover custom tools from src/MCP/Tools and app/Restify/Mcp/Tools.
     */
    protected function discoverCustomTools(): Collection
    {
        $tools = collect();
        $excludedTools = config('restify.mcp.tools.exclude', []);

        // Discover from src/MCP/Tools/*.php
        $toolDir = new \DirectoryIterator(__DIR__.'/../Tools');
        foreach ($toolDir as $toolFile) {
            if ($toolFile->isFile() && $toolFile->getExtension() === 'php') {
                $fqdn = 'Binaryk\\LaravelRestify\\MCP\\Tools\\'.$toolFile->getBasename('.php');
                if (class_exists($fqdn) && ! in_array($fqdn, $excludedTools, true)) {
                    $instance = app($fqdn);
                    $tools->push([
                        'type' => OperationTypeEnum::custom,
                        'name' => $instance->name(),
                        'title' => $instance->title(),
                        'description' => $instance->description(),
                        'class' => $fqdn,
                        'instance' => $instance,
                        'category' => ToolsCategoryEnum::CUSTOM_TOOLS->value,
                    ]);
                }
            }
        }

        // Discover wrapper tools from src/MCP/Tools/Wrapper/*.php
        $wrapperDir = __DIR__.'/../Tools/Wrapper';
        if (is_dir($wrapperDir)) {
            $wrapperToolDir = new \DirectoryIterator($wrapperDir);
            foreach ($wrapperToolDir as $toolFile) {
                if ($toolFile->isFile() && $toolFile->getExtension() === 'php') {
                    $fqdn = 'Binaryk\\LaravelRestify\\MCP\\Tools\\Wrapper\\'.$toolFile->getBasename('.php');
                    if (class_exists($fqdn) && ! in_array($fqdn, $excludedTools, true)) {
                        $instance = app($fqdn);
                        $tools->push([
                            'type' => OperationTypeEnum::wrapper,
                            'name' => $instance->name(),
                            'title' => $instance->title(),
                            'description' => $instance->description(),
                            'class' => $fqdn,
                            'instance' => $instance,
                            'category' => ToolsCategoryEnum::WRAPPER_TOOLS->value,
                        ]);
                    }
                }
            }
        }

        // Discover from app/Restify/Mcp/Tools
        $appToolsPath = app_path('Restify/Mcp/Tools');
        if (is_dir($appToolsPath)) {
            $appToolDir = new \DirectoryIterator($appToolsPath);
            foreach ($appToolDir as $toolFile) {
                if ($toolFile->isFile() && $toolFile->getExtension() === 'php') {
                    $fqdn = 'App\\Restify\\Mcp\\Tools\\'.$toolFile->getBasename('.php');
                    if (class_exists($fqdn) && ! in_array($fqdn, $excludedTools, true)) {
                        $instance = app($fqdn);
                        $tools->push([
                            'type' => OperationTypeEnum::custom,
                            'name' => $instance->name(),
                            'title' => $instance->title(),
                            'description' => $instance->description(),
                            'class' => $fqdn,
                            'instance' => $instance,
                            'category' => ToolsCategoryEnum::CUSTOM_TOOLS->value,
                        ]);
                    }
                }
            }
        }

        // Extra tools from config
        $extraTools = config('restify.mcp.tools.include', []);
        foreach ($extraTools as $toolClass) {
            if (class_exists($toolClass)) {
                $instance = app($toolClass);
                $tools->push([
                    'type' => OperationTypeEnum::custom,
                    'name' => $instance->name(),
                    'title' => $instance->title(),
                    'description' => $instance->description(),
                    'class' => $toolClass,
                    'instance' => $instance,
                    'category' => ToolsCategoryEnum::CUSTOM_TOOLS->value,
                ]);
            }
        }

        return $tools;
    }

    /**
     * Discover all repository tools (CRUD operations, actions, getters).
     */
    protected function discoverRepositoryTools(): Collection
    {
        return collect(Restify::$repositories)
            ->filter(fn (string $repo): bool => in_array(HasMcpTools::class, class_uses_recursive($repo)))
            ->flatMap(fn (string $repoClass): Collection => $this->discoverRepositoryOperations($repoClass))
            ->values();
    }

    /**
     * Discover all operations (CRUD, actions, getters) for a specific repository.
     */
    protected function discoverRepositoryOperations(string $repositoryClass): ToolsCollection
    {
        $repository = app($repositoryClass);
        $tools = ToolsCollection::make();

        if ($repository::uriKey() === 'users') {
            $tools->pushTool(
                new ProfileTool($repositoryClass),
                $repository::uriKey()
            );
        }

        if (method_exists($repository, 'mcpAllowsIndex') && $repository->mcpAllowsIndex()) {
            $tools->pushTool(
                new IndexTool($repositoryClass),
                $repository::uriKey()
            );
        }

        if (method_exists($repository, 'mcpAllowsShow') && $repository->mcpAllowsShow()) {
            $tools->pushTool(
                new ShowTool($repositoryClass),
                $repository::uriKey()
            );
        }

        if (method_exists($repository, 'mcpAllowsStore') && $repository->mcpAllowsStore()) {
            $tools->pushTool(
                new StoreTool($repositoryClass),
                $repository::uriKey()
            );
        }

        if (method_exists($repository, 'mcpAllowsUpdate') && $repository->mcpAllowsUpdate()) {
            $tools->pushTool(
                new UpdateTool($repositoryClass),
                $repository::uriKey()
            );
        }

        if (method_exists($repository, 'mcpAllowsDelete') && $repository->mcpAllowsDelete()) {
            $tools->pushTool(
                new DeleteTool($repositoryClass),
                $repository::uriKey()
            );
        }

        if (method_exists($repository, 'mcpAllowsActions') && $repository->mcpAllowsActions()) {
            $tools = $tools->merge($this->discoverActions($repositoryClass, $repository));
        }

        if (method_exists($repository, 'mcpAllowsGetters') && $repository->mcpAllowsGetters()) {
            $tools = $tools->merge($this->discoverGetters($repositoryClass, $repository));
        }

        return $tools;
    }

    protected function discoverActions(string $repositoryClass, Repository $repository): Collection
    {
        $actionRequest = app(McpActionRequest::class);

        return $repository->resolveActions($actionRequest)
            ->filter(fn ($action): bool => $action instanceof Action)
            ->filter(fn (Action $action): bool => $action->isShownOnMcp($actionRequest, $repository))
            ->filter(fn (Action $action): bool => $action->authorizedToSee($actionRequest))
            ->unique(fn (Action $action): string => $action->uriKey())
            ->map(function (Action $action) use ($repositoryClass, $repository): array {
                $instance = new ActionTool($repositoryClass, $action);

                return [
                    'type' => OperationTypeEnum::action,
                    'name' => $instance->name(),
                    'title' => $instance->title(),
                    'description' => $instance->description(),
                    'class' => ActionTool::class,
                    'instance' => $instance,
                    'repository' => $repository::uriKey(),
                    'category' => ToolsCategoryEnum::ACTIONS->value,
                    'action' => $action,
                ];
            })
            ->values();
    }

    /**
     * Discover all getters for a repository.
     */
    protected function discoverGetters(string $repositoryClass, Repository $repository): Collection
    {
        $getterRequest = app(McpGetterRequest::class);

        return $repository->resolveGetters($getterRequest)
            ->filter(fn ($getter): bool => $getter instanceof Getter)
            ->filter(fn (Getter $getter): bool => $getter->isShownOnMcp($getterRequest, $repository))
            ->filter(fn (Getter $getter): bool => $getter->authorizedToSee($getterRequest))
            ->unique(fn (Getter $getter): string => $getter->uriKey())
            ->map(function (Getter $getter) use ($repositoryClass, $repository): array {
                $instance = new GetterTool($repositoryClass, $getter);

                return [
                    'type' => OperationTypeEnum::getter,
                    'name' => $instance->name(),
                    'title' => $instance->title(),
                    'description' => $instance->description(),
                    'class' => GetterTool::class,
                    'instance' => $instance,
                    'repository' => $repository::uriKey(),
                    'category' => ToolsCategoryEnum::GETTERS->value,
                    'getter' => $getter,
                ];
            })
            ->values();
    }
}
