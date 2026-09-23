<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Company;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\RoleRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;

class CompanyRepository extends Repository
{
    public static $model = Company::class;

    public static function include(): array
    {
        return [
            'users' => BelongsToMany::make('users', UserRepository::class)->withPivot(
                Field::make('is_admin')->rules('required')
            )->canDetach(fn ($request, $pivot) => isset($_SERVER['roles.canDetach.users']) && $_SERVER['roles.canDetach.users']),

            // Deliberately never attachable, so tests can prove a request routed to
            // this relation (instead of the one named by the URL) never writes to it.
            'roles' => BelongsToMany::make('roles', RoleRepository::class)
                ->canAttach(fn () => false),
        ];
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            field('name'),
        ];
    }
}
