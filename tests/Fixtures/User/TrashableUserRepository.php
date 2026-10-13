<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

class TrashableUserRepository extends Repository
{
    public static string $model = TrashableUser::class;

    public static string $uriKey = 'trashableStaff';

    public static bool $globallySearchable = false;

    public function fields(RestifyRequest $request): array
    {
        return [
            field('name'),
        ];
    }
}
