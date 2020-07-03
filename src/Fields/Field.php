<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Traits\Make;
use Closure;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;
use JsonSerializable;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class Field extends OrganicField implements JsonSerializable
{
    use Make;

    /**
     * The resource associated with the field.
     *
     * @var  Repository
     */
    public $repository;

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
     * In case of the update, this will keep the previous value.
     * @var
     */
    public $valueBeforeUpdate;

    /**
     * Closure to resolve the index method.
     *
     * @var
     */
    private $indexCallback;

    /**
     * @var Closure
     */
    public $showCallback;

    /**
     * Callback called when the value is filled, this callback will do not override the fill action.
     * @var Closure
     */
    public $storeCallback;

    /**
     * Callback called when the value is filled from a store bulk, this callback will do not override the fill action.
     * @var Closure
     */
    public $storeBulkCallback;

    /**
     * Callback called when update.
     * @var Closure
     */
    public $updateCallback;

    /**
     * Closure be used to resolve the field's value.
     *
     * @var \Closure
     */
    public $resolveCallback;

    /**
     * Callback called when trying to fill this attribute, this callback will override the storeCallback or updateCallback.
     *
     * Make sure you assign the attribute to the model over this callback.
     *
     * @var Closure
     */
    public $fillCallback;

    /**
     * Closure be used for computed field.
     *
     * @var callable
     */
    protected $computedCallback;

    /**
     * Closure be used for the field's default value.
     *
     * @var callable
     */
    protected $defaultCallback;

    /**
     * Closure be used for the field's default value when store/update.
     *
     * @var callable
     */
    protected $appendCallback;

    /**
     * Closure be used to be called after the field value stored.
     */
    public $afterStoreCallback;

    /**
     * Closure be used to be called after the field value changed.
     */
    public $afterUpdateCallback;

    public $label;

    /**
     * Create a new field.
     *
     * @param string|callable|null $attribute
     * @param callable|null $resolveCallback
     */
    public function __construct($attribute, callable $resolveCallback = null)
    {
        $this->attribute = $attribute;

        $this->label = $attribute;

        $this->resolveCallback = $resolveCallback;

        $this->default(null);

        if ($attribute instanceof Closure || (is_callable($attribute) && is_object($attribute))) {
            $this->computedCallback = $attribute;
            $this->attribute = 'Computed';
        } else {
            $this->attribute = $attribute ?? str_replace(' ', '_', Str::lower($attribute));
        }
    }

    public function indexCallback(Closure $callback)
    {
        $this->indexCallback = $callback;

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

    public function storeCallback(Closure $callback)
    {
        $this->storeCallback = $callback;

        return $this;
    }

    public function storeCallbackCallback(Closure $callback)
    {
        $this->storeBulkCallback = $callback;

        return $this;
    }

    public function updateCallback(Closure $callback)
    {
        $this->updateCallback = $callback;

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
     * @param int|null $bulkRow
     * @return mixed|void
     */
    public function fillAttribute(RestifyRequest $request, $model, int $bulkRow = null)
    {
        $this->resolveValueBeforeUpdate($request, $model);

        if ($this->isHidden($request)) {
            if (! isset($this->appendCallback)) {
                return;
            }
        }

        if (! $this->isHidden($request) && isset($this->fillCallback)) {
            return call_user_func(
                $this->fillCallback, $request, $model, $this->attribute, $bulkRow
            );
        }

        if (isset($this->appendCallback)) {
            return $this->fillAttributeFromAppend($request, $model, $this->attribute, $bulkRow);
        }

        if ($request->isStoreRequest() && is_callable($this->storeCallback)) {
            return call_user_func(
                $this->storeCallback, $request, $model, $this->attribute, $bulkRow
            );
        }

        if ($request->isStoreBulkRequest() && is_callable($this->storeBulkCallback)) {
            return call_user_func(
                $this->storeBulkCallback, $request, $model, $this->attribute, $bulkRow
            );
        }

        if ($request->isUpdateRequest() && is_callable($this->updateCallback)) {
            return call_user_func(
                $this->updateCallback, $request, $model, $this->attribute, $bulkRow
            );
        }

        $this->fillAttributeFromRequest(
            $request, $model, $this->attribute, $bulkRow
        );
    }

    /**
     * Fill the model with value from the request.
     *
     * @param RestifyRequest $request
     * @param $model
     * @param $attribute
     * @param int|null $bulkRow
     */
    protected function fillAttributeFromRequest(RestifyRequest $request, $model, $attribute, int $bulkRow = null)
    {
        if (is_null($bulkRow)) {
            if ($request->exists($attribute) || $request->input($attribute)) {
                $model->{$attribute} = $request[$attribute] ?? $request->input($attribute);
            }

            return;
        }

        $bulkableAttribute = $bulkRow . '.' . $attribute;

        if ($request->exists($bulkableAttribute) || $request->get($bulkableAttribute)) {
            $model->{$attribute} = $request[$bulkableAttribute] ?? $request->get($bulkableAttribute);
        }
    }

    /**
     * Fill the model with the default value.
     *
     * @param RestifyRequest $request
     * @param $model
     * @param $attribute
     */
    protected function fillAttributeFromAppend(RestifyRequest $request, $model, $attribute)
    {
        if (! isset($this->appendCallback)) {
            return;
        }

        if (is_callable($this->appendCallback)) {
            return $model->{$attribute} = call_user_func(
                $this->appendCallback, $request, $model, $attribute
            );
        }

        $model->{$attribute} = $this->appendCallback;
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

    public function storeBulkRules($rules)
    {
        $this->storingBulkRules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

        return $this;
    }

    public function updateBulkRules($rules)
    {
        $this->updateBulkRules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

        return $this;
    }

    /**
     * Alias for storingRules - to maintain it consistent.
     *
     * @param $rules
     * @return $this
     */
    public function storeRules($rules)
    {
        return $this->storingRules($rules);
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

    public function getStoringBulkRules(): array
    {
        return array_merge($this->rules, $this->storingBulkRules);
    }

    public function getUpdatingRules(): array
    {
        return array_merge($this->rules, $this->updatingRules);
    }

    public function getUpdatingBulkRules(): array
    {
        return array_merge($this->rules, $this->updateBulkRules);
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

        if ($attribute === 'Computed') {
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

        if ($attribute === 'Computed') {
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
        if ($attribute === 'Computed') {
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

    protected function resolveValueBeforeUpdate(RestifyRequest $request, $repository)
    {
        if ($request->isUpdateRequest()) {
            $this->valueBeforeUpdate = $this->resolveAttribute($repository, $this->attribute);
        }
    }

    public function jsonSerialize()
    {
        return with(app(RestifyRequest::class), function ($request) {
            return [
                'attribute' => $this->label ?? $this->attribute,
                'value' => $this->resolveDefaultValue($request) ?? $this->value,
            ];
        });
    }

    public function label($label)
    {
        $this->label = $label;

        return $this;
    }

    public function serializeToValue($request)
    {
        return [
            $this->label ?? $this->attribute => $this->resolveDefaultValue($request) ?? $this->value,
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
     * @param callable $resolveCallback
     * @return $this
     */
    public function resolveCallback(callable $resolveCallback)
    {
        $this->resolveCallback = $resolveCallback;

        return $this;
    }

    public function afterUpdate(Closure $callback)
    {
        $this->afterUpdateCallback = $callback;

        return $this;
    }

    public function afterStore(Closure $callback)
    {
        $this->afterStoreCallback = $callback;

        return $this;
    }

    public function invokeAfter(RestifyRequest $request, $repository)
    {
        if ($request->isStoreRequest() && is_callable($this->afterStoreCallback)) {
            call_user_func($this->afterStoreCallback, data_get($repository, $this->attribute), $repository, $request);
        }

        if ($request->isUpdateRequest() && is_callable($this->afterUpdateCallback)) {
            call_user_func($this->afterUpdateCallback, $this->resolveAttribute($repository, $this->attribute), $this->valueBeforeUpdate, $repository, $request);
        }
    }

    /**
     * Indicate whatever the input is hidden or not.
     *
     * @param bool $callback
     * @return $this
     */
    public function hidden($callback = true)
    {
        $this->hideFromIndex($callback)
            ->hideFromShow($callback);

        $this->hiddenCallback = $callback;

        return $this;
    }

    /**
     * Append values when store/update.
     *
     * @param callable|string $value
     * @return $this
     */
    public function append($value)
    {
        $this->appendCallback = $value;

        return $this;
    }
}
