<?php

namespace App\Restify;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserRepository extends Repository
{
    public static $model = 'App\\Models\\User';

    public function fields(RestifyRequest $request): array
    {
        return [
            Field::make('name')->rules('required'),

            Field::make('email')->storingRules('required', 'unique:users')->messages([
                'required' => 'This field is required.',
            ]),
        ];
    }
}
