<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Http\Request;

class UserRepositoryWithoutMiddleware extends Repository
{
    public static $model = User::class;

    public static $withoutMiddleware = [
        EmptyMiddleware::class,
    ];

    public static $uriKey = 'user-without-middleware';

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

class EmptyMiddleware
{
    public static $called = false;

    public function handle(Request $request, $next)
    {
        static::$called = true;

        return $next($request);
    }
}
