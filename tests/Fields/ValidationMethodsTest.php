<?php

namespace Binaryk\LaravelRestify\Tests\Fields;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

class ValidationMethodsTest extends IntegrationTestCase
{
    public function test_it_can_chain_basic_validation_methods()
    {
        $field = Field::make('email')
            ->email()
            ->required();

        $this->assertContains('required', $field->getStoringRules());
        $this->assertContains('email', $field->getStoringRules());
    }

    public function test_it_can_use_nullable_validation()
    {
        $field = Field::make('optional_field')
            ->nullable()
            ->string();

        $this->assertContains('nullable', $field->getStoringRules());
        $this->assertContains('string', $field->getStoringRules());
    }

    public function test_it_can_validate_numeric_fields_with_constraints()
    {
        $field = Field::make('age')
            ->required()
            ->integer()
            ->min(18)
            ->max(100);

        $rules = $field->getStoringRules();
        $this->assertContains('required', $rules);
        $this->assertContains('integer', $rules);
        $this->assertContains('min:18', $rules);
        $this->assertContains('max:100', $rules);
    }

    public function test_it_can_validate_date_fields()
    {
        $field = Field::make('published_at')
            ->nullable()
            ->date()
            ->after('today');

        $rules = $field->getStoringRules();
        $this->assertContains('nullable', $rules);
        $this->assertContains('date', $rules);
        $this->assertContains('after:today', $rules);
    }

    public function test_it_can_validate_password_fields()
    {
        $field = Field::make('password')
            ->required()
            ->password()
            ->confirmed();

        $rules = $field->getStoringRules();
        $this->assertContains('required', $rules);
        $this->assertContains('string', $rules);
        $this->assertContains('min:8', $rules);
        $this->assertContains('confirmed', $rules);
    }

    public function test_it_can_validate_unique_fields()
    {
        $field = Field::make('email')
            ->required()
            ->email()
            ->uniqueRule('users');

        $rules = $field->getStoringRules();
        $this->assertContains('required', $rules);
        $this->assertContains('email', $rules);
        $this->assertContains('unique:users,email', $rules);
    }

    public function test_it_can_validate_between_values()
    {
        $field = Field::make('price')
            ->required()
            ->numeric()
            ->between(0, 99999.99);

        $rules = $field->getStoringRules();
        $this->assertContains('required', $rules);
        $this->assertContains('numeric', $rules);
        $this->assertContains('between:0,99999.99', $rules);
    }

    public function test_it_can_validate_url_fields()
    {
        $field = Field::make('website')
            ->nullable()
            ->url();

        $rules = $field->getStoringRules();
        $this->assertContains('nullable', $rules);
        $this->assertContains('url', $rules);
    }

    public function test_it_can_validate_json_fields()
    {
        $field = Field::make('metadata')
            ->nullable()
            ->json();

        $rules = $field->getStoringRules();
        $this->assertContains('nullable', $rules);
        $this->assertContains('json', $rules);
    }

    public function test_it_can_validate_boolean_fields()
    {
        $field = Field::make('is_active')
            ->required()
            ->boolean();

        $rules = $field->getStoringRules();
        $this->assertContains('required', $rules);
        $this->assertContains('boolean', $rules);
    }

    public function test_it_can_validate_conditional_required_fields()
    {
        $field = Field::make('phone')
            ->requiredIf('contact_method', 'phone')
            ->string();

        $rules = $field->getStoringRules();
        $this->assertContains('required_if:contact_method,phone', $rules);
        $this->assertContains('string', $rules);
    }

    public function test_it_can_validate_in_array_fields()
    {
        $field = Field::make('status')
            ->required()
            ->in(['pending', 'approved', 'rejected']);

        $rules = $field->getStoringRules();
        $this->assertContains('required', $rules);
        // The 'in' rule would be an instance of Illuminate\Validation\Rules\In
        $this->assertCount(2, $rules);
    }

    public function test_it_can_validate_file_fields()
    {
        $field = Field::make('document')
            ->required()
            ->isFile()
            ->max(5120); // 5MB in kilobytes

        $rules = $field->getStoringRules();
        $this->assertContains('required', $rules);
        $this->assertContains('file', $rules);
        $this->assertContains('max:5120', $rules);
    }

    public function test_it_can_validate_image_fields()
    {
        $field = Field::make('avatar')
            ->nullable()
            ->isImage()
            ->max(2048); // 2MB in kilobytes

        $rules = $field->getStoringRules();
        $this->assertContains('nullable', $rules);
        $this->assertContains('image', $rules);
        $this->assertContains('max:2048', $rules);
    }

    public function test_it_can_validate_ip_addresses()
    {
        $field = Field::make('ip_address')
            ->required()
            ->ip();

        $rules = $field->getStoringRules();
        $this->assertContains('required', $rules);
        $this->assertContains('ip', $rules);
    }

    public function test_it_can_combine_multiple_validation_methods()
    {
        $field = Field::make('username')
            ->required()
            ->string()
            ->min(3)
            ->max(20)
            ->alphaDash()
            ->uniqueRule('users');

        $rules = $field->getStoringRules();
        $this->assertContains('required', $rules);
        $this->assertContains('string', $rules);
        $this->assertContains('min:3', $rules);
        $this->assertContains('max:20', $rules);
        $this->assertContains('alpha_dash', $rules);
        $this->assertContains('unique:users,username', $rules);
    }
}
