<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Requests\McpActionRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ActionTool extends Tool
{
    /**
     * @var Repository|HasMcpTools
     */
    protected Repository $repository;

    protected Action $action;

    public function __construct(string $repositoryClass, Action $action)
    {
        $this->repository = app($repositoryClass);
        $this->action = $action;
    }

    public function name(): string
    {
        $repositoryUriKey = $this->repository->uriKey();
        $actionUriKey = $this->action->uriKey();

        return "{$repositoryUriKey}-{$actionUriKey}-action-tool";
    }

    public function description(): string
    {
        if ($description = $this->action->description(app(McpActionRequest::class))){
            return $description;
        }

        $repositoryUriKey = $this->repository->uriKey();
        $actionName = $this->action->name();

        $modelName = class_basename($this->repository::guessModelClassName());

        if ($this->action->isStandalone()) {
            return "Execute {$actionName} action (standalone - no models required) in the {$repositoryUriKey} repository.";
        }

        // Check if it's primarily a show action or index action
        $mcpRequest = app(McpActionRequest::class);

        $shownOnShow = $this->action->isShownOnShow($mcpRequest, $this->repository);
        $shownOnIndex = $this->action->isShownOnIndex($mcpRequest, $this->repository);

        if ($shownOnShow && ! $shownOnIndex) {
            return "Execute {$actionName} action on a specific {$modelName} record in the {$repositoryUriKey} repository.";
        } else {
            return "Execute {$actionName} action on {$modelName} records in the {$repositoryUriKey} repository.";
        }
    }

    public function schema(JsonSchema $schema): array
    {
        $repositoryClass = get_class($this->repository);
        $modelName = class_basename($repositoryClass::guessModelClassName());
        $actionName = $this->action->name();

        $fields = [];

        foreach($this->action->rules() as $field => $rules) {
            $fieldType = $this->guessTypeFromValidationRules($rules);

            $schemaField = match ($fieldType) {
                'boolean' => $schema->boolean(),
                'number' => $schema->number(),
                'array' => $schema->array(),
                default => $schema->string()
            };

            if ($this->isRequired($rules)) {
                $schemaField->required();
            }

            $fields[$field] = $schemaField;
        }

        if ($this->action->isStandalone()) {
            return $fields;
        }

        if ($this->action->isShownOnIndex(app(McpActionRequest::class), $this->repository)) {
            $fields['repositories'] = $schema->array()
                ->items(
                    $schema->string()
                )
                ->required()
                ->description("Array of {$modelName} IDs to run the {$actionName} action on.");
        } else {
            $fields['id'] = $schema->string()
                ->description("The ID of the {$modelName} to run the {$actionName} action on.")
                ->required();
        }


        return $fields;
    }

    public function handle(Request $request): Response
    {
        $mcpRequest = app(McpActionRequest::class);
        $mcpRequest->replace($request->all());
        $mcpRequest->merge([
            'mcp_repository_key' => $this->repository->uriKey(),
        ]);

        // Parse repositories string to array if provided
        if ($mcpRequest->has('repositories') && is_string($mcpRequest->input('repositories'))) {
            $repositories = json_decode($mcpRequest->input('repositories'), true) ?? [];
            $mcpRequest->merge(['repositories' => $repositories]);
        }

        // For show actions with single ID, set the route parameter
        if ($id = $mcpRequest->input('id')) {
            $mcpRequest->setRouteResolver(function () use ($id) {
                return new class($id)
                {
                    public function __construct(private $id) {}

                    public function parameter($key, $default = null)
                    {
                        return $key === 'repositoryId' ? $this->id : $default;
                    }
                };
            });
        }

        $result = $this->repository->actionTool($this->action, $mcpRequest);

        return Response::json($result);
    }

    protected function guessTypeFromValidationRules(array $rules): ?string
    {
        $ruleStrings = collect($rules)->map(function ($rule) {
            if (is_string($rule)) {
                return $rule;
            }
            if (is_object($rule)) {
                return get_class($rule);
            }

            return (string) $rule;
        })->toArray();

        // Check for specific types
        if ($this->hasAnyRule($ruleStrings, ['boolean', 'bool'])) {
            return 'boolean';
        }

        if ($this->hasAnyRule($ruleStrings, ['array'])) {
            return 'array';
        }

        if ($this->hasAnyRule($ruleStrings, ['email', 'url', 'ip', 'uuid', 'string', 'regex'])) {
            return 'string';
        }

        if ($this->hasAnyRule($ruleStrings, ['date', 'date_format:', 'before:', 'after:', 'before_or_equal:', 'after_or_equal:'])) {
            return 'string'; // Dates are typically handled as strings in schemas
        }

        if ($this->hasAnyRule($ruleStrings, ['file', 'image', 'mimes:', 'mimetypes:'])) {
            return 'string'; // Files are typically handled as strings (paths/URLs)
        }

        if ($this->hasAnyRule($ruleStrings, ['integer', 'int', 'numeric', 'between:'])) {
            return 'number';
        }

        return null;
    }

    /**
     * Check if field is required based on validation rules.
     */
    protected function isRequired(array $rules): bool
    {
        return in_array('required', $rules) ||
            collect($rules)->contains(function ($rule) {
                return is_string($rule) && str_starts_with($rule, 'required');
            });
    }

    protected function hasAnyRule(array $ruleStrings, array $rulesToCheck): bool
    {
        foreach ($ruleStrings as $rule) {
            foreach ($rulesToCheck as $check) {
                if ($rule === $check || str_starts_with($rule, $check)) {
                    return true;
                }
            }
        }

        return false;
    }
}
