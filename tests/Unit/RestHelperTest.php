<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Tests\Database\Factories\UserFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

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
    public function a_non_model_value_ahead_of_real_models_no_longer_crashes_the_batch(): void
    {
        $userA = UserFactory::one();
        $userB = UserFactory::one();

        $serialized = rest('not-a-model', $userA, $userB)->jsonSerialize();

        $this->assertCount(2, $serialized['data']);
    }
}
