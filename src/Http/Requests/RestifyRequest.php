<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RestifyRequest extends FormRequest
{
    use InteractWithRepositories;

}
