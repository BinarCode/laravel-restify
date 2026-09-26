<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

class VisibleAttributesUserRepository extends Repository
{
    public static string $model = VisibleAttributesUser::class;

    public static string $uriKey = 'visible-attributes-users';

    public static bool $globallySearchable = false;

    /**
     * @return list<Field>
     */
    public function fields(RestifyRequest $request): array
    {
        return [
            field('name'),

            field('email'),

            field('password'),

            field('remember_token')->label('rememberToken'),

            field(fn (): string => 'Hello')->label('greeting'),

            field('created_at')->hideFromShow()->hideFromIndex(),
        ];
    }
}
