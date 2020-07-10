<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class UserRepository extends Repository
{
    public static $model = User::class;

    public static $search = [
        'id',
        'name',
    ];

    public function fields(RestifyRequest $request)
    {
        return [
        ];
    }

    public function actions(RestifyRequest $request)
    {
        return [
            ActivateAction::new(),
        ];
    }
}
