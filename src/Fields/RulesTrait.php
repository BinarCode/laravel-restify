<?php

namespace Binaryk\LaravelRestify\Fields;

use Illuminate\Contracts\Validation\Rule;

/**
 * @package Binaryk\LaravelRestify\Fields;
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
trait RulesTrait
{
    /**
     * Rules for applied when store
     *
     * @var array
     */
    public $storingRules = [];

    /**
     * Rules for applied when store and update
     *
     * @var array
     */
    public $rules = [];


    /**
     * @var array
     */
    public $messages = [];

    /**
     * Validation rules for store
     * @param  callable|array|string  $rules
     * @return RulesTrait
     */
    public function storingRules($rules)
    {
        $this->storingRules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

        return $this;
    }

    /**
     * Validation rules for store
     * @param  callable|array|string  $rules
     * @return RulesTrait
     */
    public function rules($rules)
    {
        $this->rules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

        return $this;
    }

    /**
     * Validation messages
     *
     * @param  array  $messages
     * @return RulesTrait
     */
    public function messages(array $messages)
    {
        $this->messages = $messages;
        return $this;
    }

    /**
     * Validation rules for storing
     *
     * @return array
     */
    public function getStoringRules()
    {
        return array_merge($this->rules, $this->storingRules);
    }
}
