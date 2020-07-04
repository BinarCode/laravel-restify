<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class InvalidatePostAction extends Action
{

    public function handle(ActionRequest $request, Collection $models): JsonResponse
    {
        return response()->json();
    }
}
