<?php

namespace Binaryk\LaravelRestify\MCP\Actions;

use Illuminate\JsonSchema\Types\Type;

class BuildMcpExamplePayloadAction
{
    /**
     * @param array<string, Type> $schema  ActionTool::schema() output.
     * @param array<string, mixed> $bind   Known values to inject.
     * @return array<string, mixed>
     */
    public function __invoke(array $schema, array $bind): array
    {
        $payload = [];

        foreach ($schema as $key => $type) {
            if (array_key_exists($key, $bind)) {
                $payload[$key] = $bind[$key];

                continue;
            }

            $payload[$key] = $this->placeholder($type);
        }

        return $payload;
    }

    private function placeholder(Type $type): string
    {
        $serialized = $type->toArray();
        $jsonType = $serialized['type'] ?? 'string';

        $ref = new \ReflectionProperty($type, 'required');
        $required = $ref->getValue($type) === true ? 'required' : 'optional';

        return "<{$jsonType}, {$required}>";
    }
}
