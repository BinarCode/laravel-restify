<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\MCP\Actions\JsonSchemaFromRulesAction;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\JsonSchema\Types\StringType;
use PHPUnit\Framework\Attributes\Test;

/**
 * validateCurrentPassword() still had its original Illuminate\Validation\Concerns\ValidatesAttributes
 * body, which reaches for $this->container, $this->container->make('hash')
 * and the authenticated guard to check a real password. None of that exists
 * on JsonSchemaFromRulesAction, so generating the MCP input schema for any
 * repository field validated with the `current_password` rule crashed with
 * "Undefined property: JsonSchemaFromRulesAction::$container" instead of
 * describing the field, like every other validateXxx() method in the trait does.
 */
class CurrentPasswordSchemaRuleTest extends IntegrationTestCase
{
    #[Test]
    public function the_current_password_rule_describes_a_string_field_instead_of_crashing(): void
    {
        $action = new JsonSchemaFromRulesAction;
        $schema = new JsonSchemaTypeFactory;

        $result = $action($schema, [
            'password' => ['current_password'],
        ]);

        $this->assertInstanceOf(StringType::class, $result['password']);

        $serialized = $result['password']->toArray();

        $this->assertStringContainsString(
            "Must match the currently authenticated user's password",
            $serialized['description'],
        );
    }
}
