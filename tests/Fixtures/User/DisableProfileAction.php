<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Illuminate\Http\JsonResponse;

class DisableProfileAction extends Action
{
    public static $applied = [];

    public static $uriKey = 'disable_profile';

    public function handle(ActionRequest $request, string $foo = 'foo'): JsonResponse
    {
        static::$applied[] = $foo;

        return data(['success' => 'true']);
    }
}
