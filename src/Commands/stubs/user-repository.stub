<?php

namespace App\Restify;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Support\Facades\Hash;

class UserRepository extends Repository
{
    /**
     * The model the repository corresponds to.
     *
     * @var string
     */
    public static $model = 'App\\Models\\User';

    /**
     * @param  RestifyRequest  $request
     * @return array
     */
    public function fields(RestifyRequest $request)
    {
        return [
            Field::make('email')->rules('required')->storingRules('unique:users')->messages([
                'required' => 'This field is required.',
            ]),
            Field::make('password')->storeCallback(function ($value) {
                    return Hash::make($value);
                })->rules('required')->storingRules('confirmed'),
        ];
    }
}
