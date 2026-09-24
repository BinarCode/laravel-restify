<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Tests\Database\Factories\UserFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class RestHelperTest extends IntegrationTestCase
{
    #[Test]
    public function it_resolves_the_repository_registered_for_the_model(): void
    {
        $user = UserFactory::one();

        $serialized = rest($user)->jsonSerialize();

        $this->assertSame(
            UserRepository::uriKey(),
            $serialized['type'],
        );
    }

    #[Test]
    #[TestWith(['not-a-model', 2, 'first'], 'junk string ahead of two models')]
    #[TestWith(['not-a-model', 1, 'first'], 'junk string ahead of one model')]
    #[TestWith([null, 2, 'first'], 'null ahead of two models')]
    #[TestWith(['not-a-model', 2, 'last'], 'junk string after two models')]
    public function a_non_model_value_mixed_with_real_models_does_not_change_the_resolved_repository(
        mixed $junk,
        int $modelCount,
        string $position,
    ): void {
        $models = array_map(fn (): User => UserFactory::one(), range(1, $modelCount));

        $arguments = $position === 'first'
            ? [$junk, ...$models]
            : [...$models, $junk];

        $expected = rest(...$models)->jsonSerialize();

        $serialized = rest(...$arguments)->jsonSerialize();

        $this->assertEquals($expected, $serialized);
    }
}
