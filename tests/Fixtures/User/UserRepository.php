<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Repositories\UserProfile;

class UserRepository extends Repository
{
    use UserProfile;

    public static $model = User::class;

    public static $wasBooted = false;

    public static array $search = [
        'id',
        'name',
    ];

    public static array $related = [
        'posts',
    ];

    public static array $middleware = [];

    public static array $match = [
        'created_at' => RestifySearchable::MATCH_DATETIME,
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

            DisableProfileAction::new()->standalone(),
        ];
    }

    protected static function booted()
    {
        static::$wasBooted = true;
    }
}
