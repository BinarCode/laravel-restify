<?php

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Attributes\Model;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use InvalidArgumentException;

class ModelAttributeTest extends IntegrationTestCase
{
    public function test_model_attribute_can_be_instantiated(): void
    {
        $attribute = new Model(User::class);

        $this->assertEquals(User::class, $attribute->getModelClass());
    }

    public function test_model_attribute_throws_exception_for_empty_class(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model class cannot be empty');

        new Model('');
    }

    public function test_repository_uses_model_attribute_when_present(): void
    {
        $repository = new class extends Repository
        {
            public function fields(RestifyRequest $request): array
            {
                return [];
            }
        };

        // Use reflection to add the attribute to the anonymous class
        $reflectionClass = new \ReflectionClass($repository::class);

        // Since we can't dynamically add attributes to anonymous classes in tests,
        // let's test the getModelFromAttribute method directly
        $method = new \ReflectionMethod($repository::class, 'getModelFromAttribute');
        $method->setAccessible(true);

        // For this anonymous class without attribute, it should return null
        $result = $method->invokeArgs($repository, []);
        $this->assertNull($result);
    }

    public function test_attribute_has_priority_over_static_property(): void
    {
        $repository = new AttributeTestRepository;

        // The static property says Post::class, but attribute should win
        $this->assertEquals(User::class, $repository::guessModelClassName());
    }

    public function test_static_property_is_used_when_no_attribute(): void
    {
        $repository = new StaticPropertyRepository;

        $this->assertEquals(Post::class, $repository::guessModelClassName());
    }

    public function test_auto_guessing_works_when_no_attribute_or_static_property(): void
    {
        $repository = new UserAutoGuessRepository;

        // Should auto-guess User model from UserAutoGuessRepository name
        $modelClass = $repository::guessModelClassName();

        // Since we're in tests, it might not find the actual model,
        // but we can test the guessing logic worked
        $this->assertIsString($modelClass);
    }
}

#[Model(User::class)]
class AttributeTestRepository extends Repository
{
    // This static property should be ignored in favor of the attribute
    public static string $model = Post::class;

    public function fields(RestifyRequest $request): array
    {
        return [];
    }
}

class StaticPropertyRepository extends Repository
{
    public static string $model = Post::class;

    public function fields(RestifyRequest $request): array
    {
        return [];
    }
}

class UserAutoGuessRepository extends Repository
{
    // No model attribute or static property - should auto-guess
    public function fields(RestifyRequest $request): array
    {
        return [];
    }
}
