<?php

namespace Binaryk\LaravelRestify\Fields;

use Illuminate\Http\Request;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
abstract class BaseField
{
    /**
     * Conditionally load the field.
     *
     * @var bool|callable
     */
    public $when = true;

    /**
     * Conditionally load the field.
     *
     * @param callable|bool $condition
     * @param  bool  $default
     * @return $this
     */
    public function when($condition, $default = false)
    {
        $this->when = $condition ?? $default;

        return $this;
    }

    /**
     * Conditionally load the field.
     *
     * @param Request $request
     * @return bool|callable|mixed
     */
    public function filter(Request $request)
    {
        return is_callable($this->when) ? call_user_func($this->when, $request) : $this->when;
    }
}
