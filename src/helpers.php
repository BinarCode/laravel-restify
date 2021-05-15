<?php

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Http\JsonResponse;

if (! function_exists('field')) {
    function field(...$args): Field
    {
        return Field::new(...$args);
    }
}

if (! function_exists('isRestify')) {
    function isRestify(\Illuminate\Http\Request $request): bool
    {
        return Restify::isRestify($request);
    }
}

if (! function_exists('data')) {
    function data($data): JsonResponse
    {
        return response()->json([
            'data' => $data,
        ]);
    }
}
