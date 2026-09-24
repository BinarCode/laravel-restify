<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;

class RestifyRegisterRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string|Closure>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'unique:'.$this->authTable()],
            'password' => ['required', 'confirmed', 'min:6', self::scalarPasswordRule()],
        ];
    }

    private function authTable(): string
    {
        /** @var string $table */
        $table = Config::get('restify.auth.table') ?: 'users';

        return $table;
    }

    /**
     * A non-scalar password (array/object) can't be hashed - reject it with
     * a validation error instead of reaching Hash::make() and crashing.
     */
    protected static function scalarPasswordRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_scalar($value)) {
                $fail(__('The :attribute must be a string.'));
            }
        };
    }
}
