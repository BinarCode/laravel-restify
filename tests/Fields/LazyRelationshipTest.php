<?php

namespace Binaryk\LaravelRestify\Tests\Fields;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

class LazyRelationshipTest extends IntegrationTestCase
{
    public function test_field_lazy_basic_functionality()
    {
        $field = Field::make('postsTitles')->lazy('posts');
        $request = new RestifyRequest;

        // Test basic lazy functionality
        $this->assertTrue($field->isLazy($request));
        $this->assertEquals('posts', $field->getLazyRelationshipName());
        
        // Test fluent API
        $this->assertInstanceOf(Field::class, $field);
    }

    public function test_field_lazy_defaults_to_attribute_name()
    {
        $field = Field::make('posts')->lazy();
        $request = new RestifyRequest;

        $this->assertTrue($field->isLazy($request));
        $this->assertEquals('posts', $field->getLazyRelationshipName());
    }

    public function test_field_lazy_with_null_uses_attribute_name()
    {
        $field = Field::make('tags')->lazy(null);
        $request = new RestifyRequest;

        $this->assertTrue($field->isLazy($request));
        $this->assertEquals('tags', $field->getLazyRelationshipName());
    }

    public function test_field_lazy_method_is_fluent()
    {
        $field = Field::make('profileTags')
            ->lazy('tags')
            ->label('Profile Tags');

        $request = new RestifyRequest;
        
        $this->assertTrue($field->isLazy($request));
        $this->assertEquals('tags', $field->getLazyRelationshipName());
        $this->assertEquals('Profile Tags', $field->label);
    }

    public function test_non_lazy_field_returns_false()
    {
        $field = Field::make('title');
        $request = new RestifyRequest;

        $this->assertFalse($field->isLazy($request));
        $this->assertNull($field->getLazyRelationshipName());
    }
}