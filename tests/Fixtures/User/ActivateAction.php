<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Illuminate\Http\JsonResponse;

class ActivateAction extends Action
{
    public static $applied = [];

    public function handle(ActionRequest $request, User $user): JsonResponse
    {
        static::$applied[] = $user;

        return $this->response()->data(['succes' => 'true'])->respond();
    }
}
