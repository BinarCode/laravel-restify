<?php

namespace App\Restify;

use App\Models\User;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class UserRepository extends Repository
{
    public static string $model = User::class;

    public function fields(RestifyRequest $request): array
    {
        return [
            field('name')->rules('required'),

            field('email')->storingRules('required', 'unique:users')->messages([
                'required' => 'This field is required.',
            ]),
        ];
    }
}
