<?php

namespace Binaryk\LaravelRestify\MCP\AiInstructions;

use Binaryk\LaravelRestify\MCP\McpTools;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Collection;

/**
 * Render a client-agnostic markdown "AI instructions" document for a whole
 * repository: every MCP-enabled tool (CRUD + actions + getters) with its
 * description and input parameters (required/optional/type).
 *
 * Built entirely from the existing Restify MCP pipeline (McpTools), so the
 * output never drifts from what the MCP server actually exposes.
 */
class RepositoryAiInstructionsRenderer
{
    public function __invoke(string $repositoryKey): string
    {
        $repository = McpTools::getRepositoryOperations($repositoryKey);

        $tools = $this->collectTools($repository);

        $sections = $tools
            ->map(fn (array $tool): string => $this->renderTool($repositoryKey, $tool))
            ->filter()
            ->implode("\n\n");

        return $this->frontMatter($repositoryKey, $repository, $tools->count())
            ."\n\n# {$repositoryKey} — MCP tools\n\n"
            .$this->intro($repository)
            ."\n\n".$sections;
    }

    /**
     * Flatten CRUD operations, actions and getters into a single ordered list,
     * each carrying the operation type + name needed by getOperationDetails().
     *
     * @return Collection<int, array{type: string, name: ?string, tool_name: string}>
     */
    private function collectTools(array $repository): Collection
    {
        $operations = collect($repository['operations'])->map(fn (array $op): array => [
            'type' => $op['type'],
            'name' => null,
            'tool_name' => $op['name'],
        ]);

        $actions = collect($repository['actions'])->map(fn (array $a): array => [
            'type' => 'action',
            'name' => $a['name'],
            'tool_name' => $a['tool_name'],
        ]);

        $getters = collect($repository['getters'])->map(fn (array $g): array => [
            'type' => 'getter',
            'name' => $g['name'],
            'tool_name' => $g['tool_name'],
        ]);

        return $operations->concat($actions)->concat($getters)->values();
    }

    private function renderTool(string $repositoryKey, array $tool): string
    {
        $details = McpTools::getOperationDetails($repositoryKey, $tool['type'], $tool['name']);

        $schema = (new JsonSchemaTypeFactory)->object($details['schema'])->toArray();

        $heading = "## {$details['operation']}  ·  {$details['type']}";
        $description = trim((string) $details['description']);

        $parameters = $this->renderParameters($schema);

        return collect([$heading, $description, $parameters])
            ->filter(fn (string $part): bool => $part !== '')
            ->implode("\n\n");
    }

    private function renderParameters(array $schema): string
    {
        $properties = $schema['properties'] ?? [];

        if ($properties === []) {
            return '_No parameters._';
        }

        $required = $schema['required'] ?? [];

        $lines = collect($properties)
            ->map(fn (array $property, string $name): string => $this->renderParameterLine(
                $name,
                $property,
                in_array($name, $required, true),
            ))
            ->implode("\n");

        return "**Parameters**\n\n".$lines;
    }

    private function renderParameterLine(string $name, array $property, bool $isRequired, int $indent = 0): string
    {
        $type = $property['type'] ?? 'string';

        if (is_array($type)) {
            $type = implode('|', $type);
        }

        $flag = $isRequired ? 'required' : 'optional';
        $pad = str_repeat('  ', $indent);

        $line = "{$pad}- `{$name}` — {$type}, {$flag}";

        if (! empty($property['description'])) {
            $line .= '. '.trim((string) $property['description']);
        }

        $nested = $property['properties'] ?? [];

        if ($nested !== []) {
            $nestedRequired = $property['required'] ?? [];

            $line .= "\n".collect($nested)
                ->map(fn (array $child, string $childName): string => $this->renderParameterLine(
                    $childName,
                    $child,
                    in_array($childName, $nestedRequired, true),
                    $indent + 1,
                ))
                ->implode("\n");
        }

        return $line;
    }

    private function frontMatter(string $repositoryKey, array $repository, int $toolCount): string
    {
        $label = $this->yamlValue($repository['label'] ?? $repositoryKey);

        return implode("\n", [
            '---',
            "repository: {$repositoryKey}",
            "label: {$label}",
            "tools: {$toolCount}",
            'generated_by: laravel-restify',
            '---',
        ]);
    }

    private function intro(array $repository): string
    {
        $description = trim((string) ($repository['description'] ?? ''));

        $usage = 'Call these tools through the Restify MCP server (e.g. the '
            .'`execute-operation` gateway) using the tool name and the parameters described below.';

        return $description === '' ? $usage : $description."\n\n".$usage;
    }

    private function yamlValue(string $value): string
    {
        return str_contains($value, ':') ? '"'.str_replace('"', '\"', $value).'"' : $value;
    }
}
