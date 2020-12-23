<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Company;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;

class CompanyRepository extends Repository
{
    public static $model = Company::class;

    public static function related(): array
    {
        return [
            'users' => BelongsToMany::make('users', 'users', UserRepository::class)->withPivot(
                Field::make('is_admin')->rules('required')
            ),
        ];
    }

    public function fields(RestifyRequest $request)
    {
        return [
            field('name'),

            BelongsToMany::make('users', 'users', UserRepository::class)->withPivot(
                Field::make('is_admin')->rules('required')
            )->canDetach(fn ($request, $pivot) => $_SERVER['roles.canDetach.users']),
        ];
    }
}
