<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Closure;
use JsonSerializable;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class Field extends OrganicField implements JsonSerializable
{
    use RulesTrait;

    /**
     * Column name of the field.
     * @var string
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
     * @param  callable|null  $resolveCallback
     */
    public function __construct($attribute, callable $resolveCallback = null)
    {
        $this->attribute = $attribute;
    }

    /**
     * Create a new element.
     *
     * @return static
     */
    public static function fire(...$arguments)
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
        if ($request->exists($attribute)) {
            $value = $request[$attribute];

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
}
