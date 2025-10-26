<?php

namespace Binaryk\LaravelRestify\MCP\Bootstrap;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Getters\Getter;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
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
                        'type' => 'custom',
                        'name' => $instance->name(),
                        'title' => $instance->title(),
                        'description' => $instance->description(),
                        'class' => $fqdn,
                        'instance' => $instance,
                        'category' => 'Custom Tools',
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
                            'type' => 'wrapper',
                            'name' => $instance->name(),
                            'title' => $instance->title(),
                            'description' => $instance->description(),
                            'class' => $fqdn,
                            'instance' => $instance,
                            'category' => 'Wrapper Tools',
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
                            'type' => 'custom',
                            'name' => $instance->name(),
                            'title' => $instance->title(),
                            'description' => $instance->description(),
                            'class' => $fqdn,
                            'instance' => $instance,
                            'category' => 'Custom Tools',
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
                    'type' => 'custom',
                    'name' => $instance->name(),
                    'title' => $instance->title(),
                    'description' => $instance->description(),
                    'class' => $toolClass,
                    'instance' => $instance,
                    'category' => 'Custom Tools',
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
    protected function discoverRepositoryOperations(string $repositoryClass): Collection
    {
        $repository = app($repositoryClass);
        $tools = collect();

        // Profile tool (only for users repository)
        if ($repository::uriKey() === 'users') {
            $instance = new ProfileTool($repositoryClass);
            $tools->push([
                'type' => 'profile',
                'name' => $instance->name(),
                'title' => $instance->title(),
                'description' => $instance->description(),
                'class' => ProfileTool::class,
                'instance' => $instance,
                'repository' => $repository::uriKey(),
                'category' => 'Profile',
            ]);
        }

        // Index operation
        if (method_exists($repository, 'mcpAllowsIndex') && $repository->mcpAllowsIndex()) {
            $instance = new IndexTool($repositoryClass);
            $tools->push([
                'type' => 'index',
                'name' => $instance->name(),
                'title' => $instance->title(),
                'description' => $instance->description(),
                'class' => IndexTool::class,
                'instance' => $instance,
                'repository' => $repository::uriKey(),
                'category' => 'CRUD Operations',
            ]);
        }

        // Show operation
        if (method_exists($repository, 'mcpAllowsShow') && $repository->mcpAllowsShow()) {
            $instance = new ShowTool($repositoryClass);
            $tools->push([
                'type' => 'show',
                'name' => $instance->name(),
                'title' => $instance->title(),
                'description' => $instance->description(),
                'class' => ShowTool::class,
                'instance' => $instance,
                'repository' => $repository::uriKey(),
                'category' => 'CRUD Operations',
            ]);
        }

        // Store operation
        if (method_exists($repository, 'mcpAllowsStore') && $repository->mcpAllowsStore()) {
            $instance = new StoreTool($repositoryClass);
            $tools->push([
                'type' => 'store',
                'name' => $instance->name(),
                'title' => $instance->title(),
                'description' => $instance->description(),
                'class' => StoreTool::class,
                'instance' => $instance,
                'repository' => $repository::uriKey(),
                'category' => 'CRUD Operations',
            ]);
        }

        // Update operation
        if (method_exists($repository, 'mcpAllowsUpdate') && $repository->mcpAllowsUpdate()) {
            $instance = new UpdateTool($repositoryClass);
            $tools->push([
                'type' => 'update',
                'name' => $instance->name(),
                'title' => $instance->title(),
                'description' => $instance->description(),
                'class' => UpdateTool::class,
                'instance' => $instance,
                'repository' => $repository::uriKey(),
                'category' => 'CRUD Operations',
            ]);
        }

        // Delete operation
        if (method_exists($repository, 'mcpAllowsDelete') && $repository->mcpAllowsDelete()) {
            $instance = new DeleteTool($repositoryClass);
            $tools->push([
                'type' => 'delete',
                'name' => $instance->name(),
                'title' => $instance->title(),
                'description' => $instance->description(),
                'class' => DeleteTool::class,
                'instance' => $instance,
                'repository' => $repository::uriKey(),
                'category' => 'CRUD Operations',
            ]);
        }

        // Actions
        if (method_exists($repository, 'mcpAllowsActions') && $repository->mcpAllowsActions()) {
            $tools = $tools->merge($this->discoverActions($repositoryClass, $repository));
        }

        // Getters
        if (method_exists($repository, 'mcpAllowsGetters') && $repository->mcpAllowsGetters()) {
            $tools = $tools->merge($this->discoverGetters($repositoryClass, $repository));
        }

        return $tools;
    }

    /**
     * Discover all actions for a repository.
     */
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
                    'type' => 'action',
                    'name' => $instance->name(),
                    'title' => $instance->title(),
                    'description' => $instance->description(),
                    'class' => ActionTool::class,
                    'instance' => $instance,
                    'repository' => $repository::uriKey(),
                    'category' => 'Actions',
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
                    'type' => 'getter',
                    'name' => $instance->name(),
                    'title' => $instance->title(),
                    'description' => $instance->description(),
                    'class' => GetterTool::class,
                    'instance' => $instance,
                    'repository' => $repository::uriKey(),
                    'category' => 'Getters',
                    'getter' => $getter,
                ];
            })
            ->values();
    }
}
