<?php

namespace Binaryk\LaravelRestify\Services;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class ResetPasswordService
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ];
    }

    public function messages()
    {
        return [];
    }

}
