<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RestifyRequest extends FormRequest
{
    use InteractWithRepositories;

    /**
     * @return bool
     */
    public function isProduction()
    {
        return App::environment('production');
    }

    /**
     * @return bool
     */
    public function isDev()
    {
        return false === $this->isProduction();
    }
}
