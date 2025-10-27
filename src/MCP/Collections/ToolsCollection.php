<?php

namespace Binaryk\LaravelRestify\MCP\Collections;

use Binaryk\LaravelRestify\MCP\Enums\OperationTypeEnum;
use Binaryk\LaravelRestify\MCP\Enums\ToolsCategoryEnum;
use Illuminate\Support\Collection;
use Laravel\Mcp\Server\Tool;

class ToolsCollection extends Collection
{
    public function toUi(): self
    {
        return $this->map(fn (array $tool): array => [
            'name' => $tool['name'],
            'title' => $tool['title'],
            'description' => $tool['description'],
            'category' => $tool['category'],
            'type' => $tool['type'],
        ]);
    }

    public function pushTool(Tool $tool, string $repositoryKey, array $extra = []): self
    {
        return $this->push(array_merge([
            'type' => OperationTypeEnum::fromTool($tool),
            'name' => $tool->name(),
            'title' => $tool->title(),
            'description' => $tool->description(),
            'class' => get_class($tool),
            'instance' => $tool,
            'repository' => $repositoryKey,
            'category' => ToolsCategoryEnum::fromTool($tool)->value,
        ], $extra));
    }
}
