<?php

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class FieldTest extends IntegrationTest
{
    public function test_fields_can_have_custom_index_callback()
    {
        $field = Field::make('name')->indexCallback(function ($value) {
            return strtoupper($value);
        });

        $field->resolveForIndex((object) ['name' => 'Binaryk'], 'name');
        $this->assertEquals('BINARYK', $field->value);

        $field->resolveForShow((object) ['name' => 'Binaryk'], 'name');
        $this->assertEquals('Binaryk', $field->value);
    }

    public function test_fields_can_have_custom_show_callback()
    {
        $field = Field::make('name')->showCallback(function ($value) {
            return strtoupper($value);
        });

        $field->resolveForShow((object) ['name' => 'Binaryk'], 'name');
        $this->assertEquals('BINARYK', $field->value);

        $field->resolveForIndex((object) ['name' => 'Binaryk'], 'name');
        $this->assertEquals('Binaryk', $field->value);
    }

    public function test_fields_can_have_custom_resolver_callback_even_if_field_is_missing()
    {
        $field = Field::make('Name')->showCallback(function ($value, $model, $attribute) {
            return strtoupper('default');
        });

        $field->resolveForShow((object) ['name' => 'Binaryk'], 'email');

        $this->assertEquals('DEFAULT', $field->value);
    }
}
