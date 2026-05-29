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
        $nested = [];

        foreach ($schema as $key => $type) {
            $value = array_key_exists($key, $bind) ? $bind[$key] : $this->placeholder($type);

            if (! str_contains($key, '.')) {
                // A bare parent entry (e.g. 'length' => array()) is only a schema hint;
                // drop its placeholder string once dotted children have nested under it.
                if (! isset($nested[$key]) || is_array($value)) {
                    data_set($nested, $key, $value);
                }

                continue;
            }

            data_set($nested, $key, $value);
        }

        return $nested;
    }

    private function placeholder(Type $type): string
    {
        $serialized = $type->toArray();
        $jsonType = $serialized['type'] ?? 'string';

        if (is_array($jsonType)) {
            $jsonType = implode('|', $jsonType);
        }

        $required = $this->isRequired($type) ? 'required' : 'optional';

        return "<{$jsonType}, {$required}>";
    }

    private function isRequired(Type $type): bool
    {
        $attributes = (fn () => get_object_vars($this))->call($type);

        return ($attributes['required'] ?? null) === true;
    }
}
