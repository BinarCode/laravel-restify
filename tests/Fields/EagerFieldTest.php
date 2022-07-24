<?php

namespace Binaryk\LaravelRestify\Tests\Fields;

use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class EagerFieldTest extends IntegrationTest
{
    public function test_guess_repository_name_from_key(): void
    {
        $field = EagerField::make('users');

        $this->assertSame(UserRepository::class, $field->repositoryClass);
    }

    public function test_guess_repository_fails_when_key_not_found(): void
    {
        $this->expectExceptionMessage('Repository not found for the key [usersss].');

        EagerField::make('usersss');
    }
}
