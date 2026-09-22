<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;

class RestifyRegisterRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255|unique:'.Config::string('restify.auth.table', 'users'),
            'password' => 'required|confirmed|min:6',
        ];
    }
}
