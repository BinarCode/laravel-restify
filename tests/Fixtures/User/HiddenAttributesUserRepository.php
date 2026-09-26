<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

class HiddenAttributesUserRepository extends Repository
{
    public static string $model = User::class;

    public static string $uriKey = 'hidden-attributes-users';

    public static bool $globallySearchable = false;

    public function fields(RestifyRequest $request): array
    {
        return [
            field('name'),

            field('email'),

            field('password'),

            field('remember_token')->label('rememberToken'),
        ];
    }
}
