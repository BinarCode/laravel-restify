<?php

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

if (! function_exists('field')) {
    function field(...$args): Field
    {
        return Field::new(...$args);
    }
}

if (! function_exists('isRestify')) {
    function isRestify(Request $request): bool
    {
        return Restify::isRestify($request);
    }
}

if (! function_exists('data')) {
    function data(mixed $data = [], int $status = 200, array $headers = [], $options = 0): JsonResponse
    {
        return response()->json([
            'data' => $data,
        ], $status, $headers, $options);
    }
}

if (! function_exists('ok')) {
    function ok(string $message = null, int $code = 200)
    {
        if (! is_null($message)) {
            return response()->json([
                'message' => $message,
            ], $code);
        }

        return response()->json([], $code);
    }
}

if (! function_exists('id')) {
    function id(): Field
    {
        return field('id')->readonly();
    }
}
