<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Role;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Illuminate\Database\Eloquent\Model;

class RoleRepository extends Repository
{
    public static $model = Role::class;

    public function fields(RestifyRequest $request): array
    {
        return [
            field('name'),

            BelongsToMany::new('users', 'users', UserRepository::class)
            ->canAttach(fn ($request, $pivot) => $_SERVER['roles.canAttach.users'])
            ->canDetach(fn ($request, $pivot) => $_SERVER['roles.canDetach.users']),
        ];
    }

    public function attachUsers(RestifyRequest $request, Repository $repository, Model $model)
    {
        ModelHasRole::create([
            'role_id' => $model->id,
            'model_type' => User::class,
            'model_id' => $request->get('users'),
        ]);

        return $this->response()->created();
    }
}
