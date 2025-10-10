<?php

namespace Binaryk\LaravelRestify\Validation;

use Binaryk\LaravelRestify\MCP\Actions\JsonSchemaFromRulesAction;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Validation\Validator as BaseValidator;

class Validator extends BaseValidator
{
    public function toMcpSchema(?JsonSchema $schema = null): array
    {
        $schema = $schema ?? app(JsonSchema::class);
        $action = new JsonSchemaFromRulesAction;

        return $action($schema, $this->getRules());
    }
}
