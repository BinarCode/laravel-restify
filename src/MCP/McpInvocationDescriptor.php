<?php

namespace Binaryk\LaravelRestify\MCP;

final readonly class McpInvocationDescriptor
{
    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $examplePayload
     */
    public function __construct(
        public string $toolName,
        public string $repositoryUriKey,
        public string $actionUriKey,
        public string $description,
        public string $route,
        public array $schema,
        public array $examplePayload,
    ) {}

    public function toArray(): array
    {
        return [
            'tool_name' => $this->toolName,
            'repository_uri_key' => $this->repositoryUriKey,
            'action_uri_key' => $this->actionUriKey,
            'description' => $this->description,
            'route' => $this->route,
            'schema' => $this->schema,
            'example_payload' => $this->examplePayload,
        ];
    }
}
