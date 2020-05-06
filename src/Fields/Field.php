<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Closure;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;
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
     * Field value.
     *
     * @var string|callable|null
     */
    public $value;

    /**
     * Callback called when the value is filled, this callback will do not override the fill action.
     * @var Closure
     */
    public $storeCallback;

    /**
     * @var Closure
     */
    public $showCallback;

    /**
     * Closure to resolve the index method.
     *
     * @var
     */
    private $indexCallback;

    /**
     * The callback to be used to resolve the field's value.
     *
     * @var \Closure
     */
    public $resolveCallback;

    /**
     * Callback called when trying to fill this attribute, this callback will override the fill action, so make
     * sure you assign the attribute to the model over this callback.
     *
     * @var Closure
     */
    public $fillCallback;

    /**
     * The callback to be used for computed field.
     *
     * @var callable
     */
    protected $computedCallback;

    /**
     * The callback to be used for the field's default value.
     *
     * @var callable
     */
    protected $defaultCallback;

    /**
     * The resource associated with the field.
     *
     * @var  Repository
     */
    public $repository;

    /**
     * Create a new field.
     *
     * @param string|callable|null $attribute
     * @param callable|null $resolveCallback
     */
    public function __construct($attribute, callable $resolveCallback = null)
    {
        $this->attribute = $attribute;

        $this->default(null);

        $this->resolveCallback = $resolveCallback;

        if ($attribute instanceof Closure ||
            (is_callable($attribute) && is_object($attribute))) {
            $this->computedCallback = $attribute;
            $this->attribute = 'ComputedField';
        } else {
            $this->attribute = $attribute ?? str_replace(' ', '_', Str::lower($attribute));
        }
    }

    /**
     * Create a new element.
     *
     * @param array $arguments
     * @return static
     */
    public static function make(...$arguments)
    {
        return new static(...$arguments);
    }

    /**
     * Callback called when the value is filled, this callback will do not override the fill action. If fillCallback is defined
     * this will do not be called.
     *
     * @param Closure $callback
     * @return Field
     */
    public function storeCallback(Closure $callback)
    {
        $this->storeCallback = $callback;

        return $this;
    }

    /**
     * @param Closure $callback
     * @return $this
     */
    public function showCallback(Closure $callback)
    {
        $this->showCallback = $callback;

        return $this;
    }

    public function indexCallback(Closure $callback)
    {
        $this->indexCallback = $callback;

        return $this;
    }

    /**
     * Callback called when trying to fill this attribute, this callback will override the fill action, so make
     * sure you assign the attribute to the model over this callback.
     *
     * @param Closure $callback
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
     * @param RestifyRequest $request
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
     * @param RestifyRequest $request
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
     * @param $rules
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
     * @param $rules
     * @return Field
     */
    public function updatingRules($rules)
    {
        $this->updatingRules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

        return $this;
    }

    /**
     * Validation rules for store.
     * @param $rules
     * @return Field
     */
    public function rules($rules)
    {
        $this->rules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

        return $this;
    }

    public function messages(array $messages)
    {
        $this->messages = $messages;

        return $this;
    }

    public function getStoringRules(): array
    {
        return array_merge($this->rules, $this->storingRules);
    }

    public function getUpdatingRules(): array
    {
        return array_merge($this->rules, $this->updatingRules);
    }

    /**
     * Resolve the field's value for display.
     *
     * @param mixed $repository
     * @param string|null $attribute
     * @return Field|void
     */
    public function resolveForShow($repository, $attribute = null)
    {
        $attribute = $attribute ?? $this->attribute;

        if ($attribute === 'ComputedField') {
            $this->value = call_user_func($this->computedCallback, $repository);

            return;
        }

        if (! $this->showCallback) {
            $this->resolve($repository, $attribute);
        } elseif (is_callable($this->showCallback)) {
            tap($this->value ?? $this->resolveAttribute($repository, $attribute), function ($value) use ($repository, $attribute) {
                $this->value = call_user_func($this->showCallback, $value, $repository, $attribute);
            });
        }

        return $this;
    }

    public function resolveForIndex($repository, $attribute = null)
    {
        $this->repository = $repository;

        $attribute = $attribute ?? $this->attribute;

        if ($attribute === 'ComputedField') {
            $this->value = call_user_func($this->computedCallback, $repository);

            return;
        }

        if (! $this->indexCallback) {
            $this->resolve($repository, $attribute);
        } elseif (is_callable($this->indexCallback)) {
            tap($this->value ?? $this->resolveAttribute($repository, $attribute), function ($value) use ($repository, $attribute) {
                $this->value = call_user_func($this->indexCallback, $value, $repository, $attribute);
            });
        }

        return $this;
    }

    public function resolve($repository, $attribute = null)
    {
        $this->repository = $repository;
        if ($attribute === 'ComputedField') {
            $this->value = call_user_func($this->computedCallback, $repository);

            return;
        }

        if (! $this->resolveCallback) {
            $this->value = $this->resolveAttribute($repository, $attribute);
        } elseif (is_callable($this->resolveCallback)) {
            tap($this->resolveAttribute($repository, $attribute), function ($value) use ($repository, $attribute) {
                $this->value = call_user_func($this->resolveCallback, $value, $repository, $attribute);
            });
        }
    }

    /**
     * Resolve the given attribute from the given repository.
     *
     * @param mixed $repository
     * @param string $attribute
     * @return mixed
     */
    protected function resolveAttribute($repository, $attribute)
    {
        return data_get($repository, str_replace('->', '.', $attribute));
    }

    public function jsonSerialize()
    {
        return with(app(RestifyRequest::class), function ($request) {
            return [
                'attribute' => $this->attribute,
                'value' => $this->resolveDefaultValue($request) ?? $this->value,
            ];
        });
    }

    public function serializeToValue($request)
    {
        return [
            $this->attribute => $this->resolveDefaultValue($request) ?? $this->value,
        ];
    }

    /**
     * Set the callback to be used for determining the field's default value.
     *
     * @param $callback
     * @return $this
     */
    public function default($callback)
    {
        $this->defaultCallback = $callback;

        return $this;
    }

    /**
     * Resolve the default value for the field.
     *
     * @param RestifyRequest $request
     * @return callable|mixed
     */
    protected function resolveDefaultValue(RestifyRequest $request)
    {
        if (is_null($this->value) && is_callable($this->defaultCallback)) {
            return call_user_func($this->defaultCallback, $request);
        }

        return $this->defaultCallback;
    }

    /**
     * Define the callback that should be used to resolve the field's value.
     *
     * @param  callable  $resolveCallback
     * @return $this
     */
    public function resolveUsing(callable $resolveCallback)
    {
        $this->resolveCallback = $resolveCallback;

        return $this;
    }
}
