<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

trait WrapperToolHelpers
{
    /**
     * Format operation schema for display.
     *
     * Wraps the schema properties in an ObjectType before serialization to properly
     * handle required fields and other attributes according to JSON Schema spec.
     * This matches how Laravel MCP's Tool::toArray() works.
     */
    protected function formatSchemaForDisplay(array $schema): array
    {
        // JsonSchema classes are only available in Laravel 12+
        if (! interface_exists(\Illuminate\Contracts\JsonSchema\JsonSchema::class)) {
            return $schema;
        }

        $schemaFactory = new \Illuminate\JsonSchema\JsonSchemaTypeFactory;
        $objectType = $schemaFactory->object($schema);

        return $objectType->toArray();
    }

    /**
     * Generate examples from operation schema.
     *
     * After wrapping in ObjectType, the schema has a 'properties' key containing all fields.
     */
    protected function generateExamplesFromSchema(array $schema, string $operationType): array
    {
        $examples = [];
        $properties = $schema['properties'] ?? [];

        switch ($operationType) {
            case 'index':
                $examples[] = [
                    'description' => 'Basic pagination',
                    'parameters' => [
                        'page' => 1,
                        'perPage' => 15,
                    ],
                ];

                if (isset($properties['search'])) {
                    $examples[] = [
                        'description' => 'Search with pagination',
                        'parameters' => [
                            'search' => 'example search term',
                            'page' => 1,
                            'perPage' => 15,
                        ],
                    ];
                }

                if (isset($properties['include'])) {
                    $examples[] = [
                        'description' => 'With relationships',
                        'parameters' => [
                            'page' => 1,
                            'perPage' => 15,
                            'include' => 'posts,comments',
                        ],
                    ];
                }
                break;

            case 'show':
                $examples[] = [
                    'description' => 'Show single record',
                    'parameters' => [
                        'id' => '1',
                    ],
                ];

                if (isset($properties['include'])) {
                    $examples[] = [
                        'description' => 'Show with relationships',
                        'parameters' => [
                            'id' => '1',
                            'include' => 'posts,comments',
                        ],
                    ];
                }
                break;

            case 'store':
                $exampleParams = [];
                foreach ($properties as $key => $field) {
                    if ($key === 'include') {
                        continue;
                    }

                    $exampleParams[$key] = $this->generateExampleValue($key, $field);
                }

                if (! empty($exampleParams)) {
                    $examples[] = [
                        'description' => 'Create new record',
                        'parameters' => $exampleParams,
                    ];
                }
                break;

            case 'update':
                $exampleParams = ['id' => '1'];
                foreach ($properties as $key => $field) {
                    if (in_array($key, ['id', 'include'])) {
                        continue;
                    }

                    $exampleParams[$key] = $this->generateExampleValue($key, $field);
                }

                if (count($exampleParams) > 1) {
                    $examples[] = [
                        'description' => 'Update existing record',
                        'parameters' => $exampleParams,
                    ];
                }
                break;

            case 'delete':
                $examples[] = [
                    'description' => 'Delete a record',
                    'parameters' => [
                        'id' => '1',
                    ],
                ];
                break;
        }

        return $examples;
    }

    /**
     * Generate example value based on field name and type.
     */
    protected function generateExampleValue(string $fieldName, $fieldSchema): mixed
    {
        if (is_object($fieldSchema) && method_exists($fieldSchema, 'toArray')) {
            $fieldArray = $fieldSchema->toArray();
            $type = $fieldArray['type'] ?? 'string';
        } elseif (is_array($fieldSchema)) {
            $type = $fieldSchema['type'] ?? 'string';
        } else {
            $type = 'string';
        }

        return match ($type) {
            'boolean' => true,
            'number', 'integer' => $this->generateNumberExample($fieldName),
            'array' => [],
            default => $this->generateStringExample($fieldName),
        };
    }

    /**
     * Generate number example based on field name.
     */
    protected function generateNumberExample(string $fieldName): int|float
    {
        $fieldName = strtolower($fieldName);

        if (str_contains($fieldName, 'price') || str_contains($fieldName, 'amount')) {
            return 99.99;
        }

        if (str_contains($fieldName, 'age')) {
            return 25;
        }

        if (str_contains($fieldName, 'year')) {
            return 2024;
        }

        if (str_ends_with($fieldName, '_id')) {
            return 1;
        }

        return 1;
    }

    /**
     * Generate string example based on field name.
     */
    protected function generateStringExample(string $fieldName): string
    {
        $fieldName = strtolower($fieldName);

        if (str_contains($fieldName, 'email')) {
            return 'user@example.com';
        }

        if (str_contains($fieldName, 'name')) {
            return 'Example Name';
        }

        if (str_contains($fieldName, 'title')) {
            return 'Example Title';
        }

        if (str_contains($fieldName, 'description')) {
            return 'Example description';
        }

        if (str_contains($fieldName, 'url')) {
            return 'https://example.com';
        }

        if (str_contains($fieldName, 'phone')) {
            return '+1234567890';
        }

        return 'example value';
    }

    /**
     * Build error response.
     */
    protected function buildErrorResponse(string $message, ?string $code = null): array
    {
        $response = [
            'error' => $message,
        ];

        if ($code) {
            $response['code'] = $code;
        }

        return $response;
    }

    /**
     * Build success response.
     */
    protected function buildSuccessResponse(array $data, ?string $message = null): array
    {
        $response = [
            'success' => true,
            'data' => $data,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        return $response;
    }
}
