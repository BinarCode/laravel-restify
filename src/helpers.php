<?php

use Binaryk\LaravelRestify\Fields\Field;

if (! function_exists('field')) {
    function field(...$args)
    {
        return Field::new(...$args);
    }
}

if (! function_exists('data')) {
    function data($data)
    {
        return response()->json([
            'data' => $data,
        ]);
    }
}

if (! function_exists('restify')) {
    function restify(...$args)
    {
    }
}
