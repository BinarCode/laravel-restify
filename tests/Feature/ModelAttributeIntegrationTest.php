<?php

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Attributes\Model;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

class ModelAttributeIntegrationTest extends IntegrationTestCase
{
    public function test_repository_with_model_attribute_resolves_correct_model(): void
    {
        $repository = new TestRepositoryWithAttribute;

        $this->assertInstanceOf(User::class, $repository::newModel());
        $this->assertEquals(User::class, $repository::guessModelClassName());
    }

    public function test_backward_compatibility_with_static_property_still_works(): void
    {
        $repository = new TestRepositoryWithStaticProperty;

        $this->assertInstanceOf(Post::class, $repository::newModel());
        $this->assertEquals(Post::class, $repository::guessModelClassName());
    }

    public function test_attribute_takes_precedence_over_static_property(): void
    {
        $repository = new TestRepositoryWithBoth;

        // Even though static property says Post, attribute should win with User
        $this->assertInstanceOf(User::class, $repository::newModel());
        $this->assertEquals(User::class, $repository::guessModelClassName());
    }

    public function test_repository_without_attribute_or_property_uses_auto_guessing(): void
    {
        $repository = new TestUserRepository;

        // Should try to guess User model from TestUserRepository name
        $modelClass = $repository::guessModelClassName();

        // In test environment, we expect it to find User::class or fall back to NullModel
        $this->assertTrue(
            $modelClass === User::class ||
            $modelClass === 'Binaryk\LaravelRestify\Repositories\NullModel'
        );
    }

    public function test_model_attribute_with_string_class_name(): void
    {
        $repository = new TestRepositoryWithStringAttribute;

        $this->assertEquals(User::class, $repository::guessModelClassName());
        $this->assertInstanceOf(User::class, $repository::newModel());
    }
}

#[Model(User::class)]
class TestRepositoryWithAttribute extends Repository
{
    public function fields(RestifyRequest $request): array
    {
        return [];
    }
}

class TestRepositoryWithStaticProperty extends Repository
{
    public static string $model = Post::class;

    public function fields(RestifyRequest $request): array
    {
        return [];
    }
}

#[Model(User::class)]
class TestRepositoryWithBoth extends Repository
{
    // This should be ignored in favor of the attribute
    public static string $model = Post::class;

    public function fields(RestifyRequest $request): array
    {
        return [];
    }
}

class TestUserRepository extends Repository
{
    // No model defined - should auto-guess from class name
    public function fields(RestifyRequest $request): array
    {
        return [];
    }
}

#[Model('Binaryk\LaravelRestify\Tests\Fixtures\User\User')]
class TestRepositoryWithStringAttribute extends Repository
{
    public function fields(RestifyRequest $request): array
    {
        return [];
    }
}
