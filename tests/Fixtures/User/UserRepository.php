<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Repositories\UserProfile;

class UserRepository extends Repository
{
    use UserProfile;

    public static string $model = User::class;

    public static bool $wasBooted = false;

    public static array $search = [
        'id',
        'name',
    ];

    public static array $related = [
        'posts',
    ];

    public static array $middleware = [];

    public static array $match = [
        'id' => 'int',
        'created_at' => RestifySearchable::MATCH_DATETIME,
    ];

    public function fields(RestifyRequest $request): array
    {
        return [
            field('name')->rules('sometimes', 'nullable', 'min:4'),

            field('email')->rules('required', 'unique:users'),

            field('password'),
        ];
    }

    public function actions(RestifyRequest $request): array
    {
        return [
            ActivateAction::new(),

            DisableProfileAction::new()->standalone(),
        ];
    }

    protected static function booted(): void
    {
        static::$wasBooted = true;
    }
}
