<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Closure;
use Illuminate\Contracts\Validation\Rule;
use JsonSerializable;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class Field extends OrganicField implements JsonSerializable
{
    /**
     * Column name of the field.
     * @var string|callable|null
     */
    public $attribute;

    /**
     * Callback called when the value is filled, this callback will do not override the fill action.
     * @var Closure
     */
    public $storeCallback;

    /**
     * Callback called when trying to fill this attribute, this callback will override the fill action, so make
     * sure you assign the attribute to the model over this callback.
     *
     * @var Closure
     */
    public $fillCallback;

    /**
     * Create a new field.
     *
     * @param  string|callable|null  $attribute
     */
    public function __construct($attribute)
    {
        $this->attribute = $attribute;
    }

    /**
     * Create a new element.
     *
     * @param  array  $arguments
     * @return static
     */
    public static function make(...$arguments)
    {
        return new static(...$arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [];
    }

    /**
     * Callback called when the value is filled, this callback will do not override the fill action. If fillCallback is defined
     * this will do not be called.
     *
     * @param  Closure  $callback
     * @return Field
     */
    public function storeCallback(Closure $callback)
    {
        $this->storeCallback = $callback;

        return $this;
    }

    /**
     * Callback called when trying to fill this attribute, this callback will override the fill action, so make
     * sure you assign the attribute to the model over this callback.
     *
     * @param  Closure  $callback
     * @return $this
     */
    public function fillCallback(Closure $callback)
    {
        $this->fillCallback = $callback;

        return $this;
    }

    /**
     * Fill attribute with value from the request or delegate this action to the user defined callback.
     *
     * @param  RestifyRequest  $request
     * @param $model
     * @return mixed|void
     */
    public function fillAttribute(RestifyRequest $request, $model)
    {
        if (isset($this->fillCallback)) {
            return call_user_func(
                $this->fillCallback, $request, $model, $this->attribute
            );
        }

        return $this->fillAttributeFromRequest(
            $request, $model, $this->attribute
        );
    }

    /**
     * Fill the model with value from the request.
     *
     * @param  RestifyRequest  $request
     * @param $model
     * @param $attribute
     */
    protected function fillAttributeFromRequest(RestifyRequest $request, $model, $attribute)
    {
        if ($request->exists($attribute) || $request->get($attribute)) {
            $value = $request[$attribute] ?? $request->get($attribute);

            $model->{$attribute} = is_callable($this->storeCallback) ? call_user_func($this->storeCallback, $value, $request, $model) : $value;
        }
    }

    /**
     * @return callable|string|null
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * Validation rules for store.
     * @param  callable|array|string  $rules
     * @return Field
     */
    public function storingRules($rules)
    {
        $this->storingRules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

        return $this;
    }

    /**
     * Validation rules for update.
     *
     * @param  callable|array|string  $rules
     * @return Field
     */
    public function updatingRules($rules)
    {
        $this->updatingRules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

        return $this;
    }

    /**
     * Validation rules for store.
     * @param  callable|array|string  $rules
     * @return Field
     */
    public function rules($rules)
    {
        $this->rules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

        return $this;
    }

    /**
     * Validation messages.
     *
     * @param  array  $messages
     * @return Field
     */
    public function messages(array $messages)
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * Validation rules for storing.
     *
     * @return array
     */
    public function getStoringRules()
    {
        return array_merge($this->rules, $this->storingRules);
    }

    /**
     * @return array
     */
    public function getUpdatingRules()
    {
        return array_merge($this->rules, $this->updatingRules);
    }
}
