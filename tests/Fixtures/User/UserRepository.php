<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Fields\Field;
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

    public static $related = [
        'posts',
    ];

    public function fields(RestifyRequest $request)
    {
        return [
            Field::new('name')->rules('sometimes', 'nullable', 'min:4'),

            Field::new('email')->rules('required', 'unique:users'),

            Field::new('password'),
        ];
    }

    public function actions(RestifyRequest $request)
    {
        return [
            ActivateAction::new(),
        ];
    }
}
