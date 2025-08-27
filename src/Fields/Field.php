<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Fields\Concerns\CanMatch;
use Binaryk\LaravelRestify\Fields\Concerns\CanSearch;
use Binaryk\LaravelRestify\Fields\Concerns\CanSort;
use Binaryk\LaravelRestify\Fields\Concerns\HasAction;
use Binaryk\LaravelRestify\Fields\Concerns\ValidationMethods;
use Binaryk\LaravelRestify\Fields\Contracts\Matchable;
use Binaryk\LaravelRestify\Fields\Contracts\Sortable;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\FieldMcpSchemaDetection;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Traits\Make;
use Closure;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use JsonSerializable;
use ReturnTypeWillChange;

class Field extends OrganicField implements JsonSerializable, Matchable, Sortable
{
    use CanMatch;
    use CanSearch;
    use CanSort;
    use FieldMcpSchemaDetection;
    use HasAction;
    use Make;
    use ValidationMethods;

    /**
     * The resource associated with the field.
     *
     * @var Repository
     */
    public $repository;

    public Repository $parentRepository;

    /**
     * Column name of the field.
     *
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
     */
    public $valueBeforeUpdate;

    /**
     * Closure to resolve the index method.
     */
    private $indexCallback;

    /**
     * @var Closure
     */
    public $showCallback;

    /**
     * Callback called when the value is filled, this callback will do not override the fill action.
     *
     * @var Closure
     */
    public $storeCallback;

    /**
     * Callback called when the value is filled from a store bulk, this callback will do not override the fill action.
     *
     * @var Closure
     */
    public $storeBulkCallback;

    /**
     * Callback called when update.
     *
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
    protected $valueCallback;

    protected $fillDefaultCallback;

    /**
     * Closure be used to be called after the field value stored.
     */
    public $afterStoreCallback;

    /**
     * Closure be used to be called after the field value changed.
     */
    public $afterUpdateCallback;

    public $label;

    public $toolInputSchemaCallback = null;

    /**
     * Create a new field.
     *
     * @param  string|callable|null  $attribute
     * @param  callable|null  $resolveCallback
     */
    public function __construct($attribute, callable|Closure|null $resolveCallback = null)
    {
        $this->attribute = $attribute;

        $this->label = $attribute;

        $this->resolveCallback = $resolveCallback;

        $this->default(null);

        if ($attribute instanceof Closure || (is_callable($attribute) && is_object($attribute))) {
            $this->computedCallback = $attribute;
            $this->attribute = 'Computed';
            $this->readonly();
        } else {
            $this->attribute = $attribute ?? str_replace(' ', '_', Str::lower($attribute));
        }
    }

    public function indexCallback(callable|Closure $callback)
    {
        $this->indexCallback = $callback;

        return $this;
    }

    /**
     * @param  Closure  $callback
     * @return $this
     */
    public function showCallback(callable|Closure $callback)
    {
        $this->showCallback = $callback;

        return $this;
    }

    public function storeCallback(callable|Closure $callback)
    {
        $this->storeCallback = $callback;

        return $this;
    }

    public function storeBulkCallback(callable|Closure $callback)
    {
        $this->storeBulkCallback = $callback;

        return $this;
    }

    public function updateCallback(callable|Closure $callback)
    {
        $this->updateCallback = $callback;

        return $this;
    }

    /**
     * Callback called when trying to fill this attribute, this callback will override the fill action, so make
     * sure you assign the attribute to the model over this callback.
     *
     * @param  Closure  $callback
     * @return $this
     */
    public function fillCallback(callable|Closure $callback)
    {
        $this->fillCallback = $callback;

        return $this;
    }

    /**
     * This is called after the fill and store callbacks.
     *
     * You can pass a callable or a value, and it will be attached to the model if no value provided otherwise.
     *
     * Imagine it's like `attributes` in the model.
     *
     * @return $this
     */
    public function defaultCallback(mixed $callback)
    {
        $this->fillDefaultCallback = $callback;

        return $this;
    }

    /**
     * Fill attribute with value from the request or delegate this action to the user defined callback.
     *
     * @return mixed|void
     */
    public function fillAttribute(RestifyRequest $request, $model, ?int $bulkRow = null)
    {
        $this->resolveValueBeforeUpdate($request, $model);

        if (is_callable($this->fillCallback)) {
            return call_user_func(
                $this->fillCallback,
                $request,
                $model,
                $this->label ?? $this->attribute,
                $bulkRow
            );
        }

        if ($this->isHidden($request)) {
            return $this->fillAttributeFromValue(
                $request,
                $model,
                $this->label ?? $this->attribute
            );
        }

        $this->fillAttributeFromRequest(
            $request,
            $model,
            $this->label ?? $this->attribute,
            $bulkRow
        );

        $this->fillAttributeFromCallback(
            $request,
            $model,
            $this->label ?? $this->attribute,
            $bulkRow
        );

        $this->fillAttributeFromDefault(
            $request,
            $model,
            $this->label ?? $this->attribute
        );

        $this->fillAttributeFromValue(
            $request,
            $model,
            $this->label ?? $this->attribute
        );

        return $this;
    }

    /**
     * Fill the model with value from the request.
     */
    protected function fillAttributeFromRequest(RestifyRequest $request, $model, $attribute, ?int $bulkRow = null)
    {
        $attribute = is_null($bulkRow)
            ? $attribute
            : "{$bulkRow}.{$attribute}";

        if (! ($request->exists($attribute) || $request->input($attribute))) {
            return;
        }

        tap(
            ($request->input($attribute) ?? $request[$attribute]),
            fn ($value) => $model->{$this->attribute} = $request->has($attribute)
                ? $value
                : $model->{$this->attribute}
        );
    }

    /**
     * Fill the model with value from the callback.
     */
    protected function fillAttributeFromCallback(RestifyRequest $request, $model, $attribute, ?int $bulkRow = null)
    {
        if (is_callable($cb = $this->guessBeforeFillableCallable($request))) {
            $value = $request->input($attribute ?? $this->attribute);

            if ($this instanceof File) {
                $value = $request->file($attribute ?? $this->attribute);
            }

            $model->{$this->attribute} = $cb($value);
        }
    }

    /**
     * Fill the model with the value from value.
     *
     * @return Field
     */
    protected function fillAttributeFromValue(RestifyRequest $request, $model, $attribute)
    {
        if (! isset($this->valueCallback)) {
            return $this;
        }

        $model->{$attribute} = is_callable($this->valueCallback)
            ? call_user_func($this->valueCallback, $request, $model, $attribute)
            : $this->valueCallback;

        return $this;
    }

    protected function fillAttributeFromDefault(RestifyRequest $request, $model, $attribute)
    {
        if ($model->{$attribute}) {
            return $this;
        }

        if (! isset($this->fillDefaultCallback)) {
            return $this;
        }

        $model->{$attribute} = is_callable($this->fillDefaultCallback)
            ? call_user_func($this->fillDefaultCallback, $request, $model, $attribute)
            : $this->fillDefaultCallback;

        return $this;
    }

    /**
     * @return callable|string|null
     */
    public function getAttribute()
    {
        return $this->label ?? $this->attribute;
    }

    /**
     * Validation rules for store.
     *
     * @return Field
     */
    public function storingRules($rules)
    {
        $this->storingRules = ($rules instanceof Rule || is_string($rules) || $rules instanceof Unique) ? func_get_args() : $rules;

        return $this;
    }

    public function storeBulkRules($rules)
    {
        $this->storingBulkRules = ($rules instanceof Rule || is_string($rules) || $rules instanceof Unique) ? func_get_args() : $rules;

        return $this;
    }

    public function updateBulkRules($rules)
    {
        $this->updateBulkRules = ($rules instanceof Rule || is_string($rules) || $rules instanceof Unique) ? func_get_args() : $rules;

        return $this;
    }

    /**
     * Alias for storingRules - to maintain it consistent.
     *
     * @return $this
     */
    public function storeRules($rules)
    {
        return $this->storingRules($rules);
    }

    /**
     * Validation rules for update.
     *
     * @return Field
     */
    public function updatingRules($rules)
    {
        $this->updatingRules = ($rules instanceof Rule || is_string($rules) || $rules instanceof Unique) ? func_get_args() : $rules;

        return $this;
    }

    /**
     * Validation rules for store.
     *
     * @return Field
     */
    public function rules($rules)
    {
        $newRules = ($rules instanceof Rule || is_string($rules) || $rules instanceof Unique) ? func_get_args() : $rules;

        if (! is_array($newRules)) {
            $newRules = [$newRules];
        }

        $this->rules = array_merge($this->rules, $newRules);

        return $this;
    }

    public function messages(array $messages)
    {
        $this->messages = $messages;

        return $this;
    }

    public function serializeMessages(): array
    {
        $messages = [];

        foreach ($this->messages as $ruleFor => $message) {
            $messages[$this->getAttribute().'.'.$ruleFor] = $message;
        }

        return $messages;
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
     * Determine if the attribute is computed.
     *
     * @return bool
     */
    public function computed()
    {
        return (is_callable($this->attribute) && ! is_string($this->attribute)) ||
            is_callable($this->computedCallback) || $this->attribute == 'Computed';
    }

    /**
     * Resolve the attribute's value for display.
     *
     * @param  mixed  $repository
     * @param  string|null  $attribute
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
            tap(
                $this->value ?? $this->resolveAttribute($repository, $attribute),
                function ($value) use ($repository, $attribute) {
                    $this->value = call_user_func($this->showCallback, $value, $repository, $attribute);
                }
            );
        }

        return $this;
    }

    public function resolveForIndex($repository, $attribute = null)
    {
        $this->repository = $repository;

        $attribute = $attribute ?? $this->attribute;

        if ($attribute === 'Computed') {
            $this->value = call_user_func($this->computedCallback, $repository);

            return $this;
        }

        if (! $this->indexCallback) {
            $this->resolve($repository, $attribute);
        } elseif (is_callable($this->indexCallback)) {
            tap(
                $this->value ?? $this->resolveAttribute($repository, $attribute),
                function ($value) use ($repository, $attribute) {
                    $this->value = call_user_func($this->indexCallback, $value, $repository, $attribute);
                }
            );
        }

        return $this;
    }

    public function resolve($repository, $attribute = null)
    {
        $this->repository = $repository;

        $attribute = $attribute ?? $this->attribute;

        if ($attribute === 'Computed') {
            $this->value = call_user_func($this->computedCallback, $repository);

            return $this;
        }

        if (! $this->resolveCallback) {
            $this->value = $this->resolveAttribute($repository, $attribute);
        } elseif (is_callable($this->resolveCallback)) {
            tap($this->resolveAttribute($repository, $attribute), function ($value) use ($repository, $attribute) {
                $this->value = call_user_func($this->resolveCallback, $value, $repository, $attribute);
            });
        }

        return $this;
    }

    /**
     * Resolve the given attribute from the given repository.
     *
     * @param  mixed  $repository
     * @param  string  $attribute
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

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return with(app(RestifyRequest::class), function ($request) {
            return [
                'attribute' => $this->label ?? $this->attribute,
                'value' => $this->value ?? $this->resolveDefaultValue($request),
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
            $this->label ?? $this->attribute => $this->value ?? $this->resolveDefaultValue($request),
        ];
    }

    /**
     * Set the callback to be used for determining the field's default value.
     *
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
     * @return $this
     */
    public function resolveCallback(callable $resolveCallback)
    {
        $this->resolveCallback = $resolveCallback;

        return $this;
    }

    public function afterUpdate(callable|Closure $callback)
    {
        $this->afterUpdateCallback = $callback;

        return $this;
    }

    public function afterStore(callable|Closure $callback)
    {
        $this->afterStoreCallback = $callback;

        return $this;
    }

    public function invokeAfter(RestifyRequest $request, Model $model): void
    {
        if ($request->isStoreRequest() && is_callable($this->afterStoreCallback)) {
            call_user_func(
                $this->afterStoreCallback,
                data_get($model, $this->attribute),
                $model,
                $request
            );
        }

        if ($request->isUpdateRequest() && is_callable($this->afterUpdateCallback)) {
            call_user_func(
                $this->afterUpdateCallback,
                $this->resolveAttribute($model, $this->attribute),
                $this->valueBeforeUpdate,
                $model,
                $request
            );
        }
    }

    /**
     * Indicate whatever the input is hidden or not.
     *
     * @param  bool  $callback
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
     * Force set values when store/update.
     *
     * @param  callable|string  $value
     * @return $this
     */
    public function value($value)
    {
        $this->valueCallback = $value;

        return $this;
    }

    /**
     * @return $this
     *
     * @deprecated
     */
    public function append($value)
    {
        return $this->value($value);
    }

    public function setRepository(Repository $repository): Field
    {
        $this->repository = $repository;

        return $this;
    }

    public function setParentRepository(Repository $repository): Field
    {
        $this->parentRepository = $repository;

        return $this;
    }

    private function guessBeforeFillableCallable(RestifyRequest $request): Closure|callable|null
    {
        if ($request->isUpdateRequest()) {
            return $this->updateCallback;
        }

        if ($request->isStoreBulkRequest()) {
            return $this->storeBulkCallback;
        }

        return $this->storeCallback;
    }

    public function file(): File
    {
        return File::make($this->attribute);
    }

    public function image(): Image
    {
        return Image::make($this->attribute);
    }

    /**
     * Guess the field type based on validation rules, field class, and attribute patterns.
     */
    public function guessFieldType(): string
    {
        // Check field class type first
        $fieldType = $this->guessTypeFromFieldClass();
        if ($fieldType) {
            return $fieldType;
        }

        // Check validation rules
        $ruleType = $this->guessTypeFromValidationRules();
        if ($ruleType) {
            return $ruleType;
        }

        // Check attribute name patterns
        $attributeType = $this->guessTypeFromAttributeName();
        if ($attributeType) {
            return $attributeType;
        }

        // Default to string
        return 'string';
    }

    /**
     * Guess type from field class name.
     */
    protected function guessTypeFromFieldClass(): ?string
    {
        $className = class_basename(static::class);

        return match ($className) {
            'Boolean', 'BooleanField' => 'boolean',
            'Number', 'Integer', 'Decimal', 'Float' => 'number',
            'Email' => 'string',
            'Password' => 'string',
            'Textarea' => 'string',
            'Text', 'TextField' => 'string',
            'Date', 'DateTime' => 'string',
            'File', 'Image' => 'string',
            'Select', 'MultiSelect' => 'array',
            'BelongsTo', 'HasOne', 'HasMany', 'BelongsToMany' => 'object',
            default => null
        };
    }

    /**
     * Guess type from validation rules.
     */
    protected function guessTypeFromValidationRules(): ?string
    {
        $allRules = array_merge($this->rules, $this->storingRules, $this->updatingRules);

        // Convert rule objects to strings for checking
        $ruleStrings = collect($allRules)->map(function ($rule) {
            if (is_string($rule)) {
                return $rule;
            }
            if (is_object($rule)) {
                return get_class($rule);
            }

            return (string) $rule;
        })->toArray();

        // Check for specific types
        if ($this->hasAnyRule($ruleStrings, ['boolean', 'bool'])) {
            return 'boolean';
        }

        if ($this->hasAnyRule($ruleStrings, ['integer', 'int', 'numeric', 'min:', 'max:', 'between:'])) {
            return 'number';
        }

        if ($this->hasAnyRule($ruleStrings, ['array'])) {
            return 'array';
        }

        if ($this->hasAnyRule($ruleStrings, ['email', 'url', 'ip', 'uuid'])) {
            return 'string';
        }

        if ($this->hasAnyRule($ruleStrings, ['date', 'date_format:', 'before:', 'after:', 'before_or_equal:', 'after_or_equal:'])) {
            return 'string'; // Dates are typically handled as strings in schemas
        }

        if ($this->hasAnyRule($ruleStrings, ['file', 'image', 'mimes:', 'mimetypes:'])) {
            return 'string'; // Files are typically handled as strings (paths/URLs)
        }

        return null;
    }

    /**
     * Guess type from attribute name patterns.
     */
    protected function guessTypeFromAttributeName(): ?string
    {
        $attribute = $this->attribute;

        if (! is_string($attribute)) {
            return null;
        }

        $attribute = strtolower($attribute);

        // Boolean patterns
        if (preg_match('/^(is_|has_|can_|should_|will_|was_|were_)/', $attribute) ||
            in_array($attribute, ['active', 'enabled', 'disabled', 'verified', 'published', 'featured', 'public', 'private'])) {
            return 'boolean';
        }

        // Number patterns
        if (preg_match('/_(id|count|number|amount|price|cost|total|sum|quantity|qty)$/', $attribute) ||
            in_array($attribute, ['id', 'age', 'year', 'month', 'day', 'hour', 'minute', 'second', 'weight', 'height', 'size'])) {
            return 'number';
        }

        // Date patterns
        if (preg_match('/_(at|date|time)$/', $attribute) ||
            in_array($attribute, ['created_at', 'updated_at', 'deleted_at', 'published_at', 'birthday', 'date_of_birth'])) {
            return 'string';
        }

        // Email pattern
        if (str_contains($attribute, 'email')) {
            return 'string';
        }

        // Password pattern
        if (str_contains($attribute, 'password')) {
            return 'string';
        }

        // Array patterns (JSON fields)
        if (preg_match('/_(json|data|metadata|config|settings|options)$/', $attribute) ||
            str_contains($attribute, 'tags')) {
            return 'array';
        }

        return null;
    }

    /**
     * Check if any of the given rules exist in the rule strings.
     */
    protected function hasAnyRule(array $ruleStrings, array $rulesToCheck): bool
    {
        foreach ($ruleStrings as $rule) {
            foreach ($rulesToCheck as $check) {
                if ($rule === $check || str_starts_with($rule, $check)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Set a custom callback for defining the tool schema.
     *
     * @return $this
     */
    public function toolSchema(callable|Closure $callback): self
    {
        $this->toolInputSchemaCallback = $callback;

        return $this;
    }
}
