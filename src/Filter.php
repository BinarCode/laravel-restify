<?php

namespace Binaryk\LaravelRestify;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Traits\Make;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use JsonSerializable;

abstract class Filter implements JsonSerializable
{
    use Make;

    public $type = 'value';

    public $value;

    public $canSeeCallback;

    public static $uriKey;

    public function __construct()
    {
        $this->booted();
    }

    protected function booted()
    {
        //
    }

    abstract public function filter(RestifyRequest $request, $query, $value);

    public function canSee(Closure $callback)
    {
        $this->canSeeCallback = $callback;

        return $this;
    }

    public function authorizedToSee(RestifyRequest $request)
    {
        return $this->canSeeCallback ? call_user_func($this->canSeeCallback, $request) : true;
    }

    public function key()
    {
        return static::class;
    }

    protected function getType()
    {
        return $this->type;
    }

    public function options(Request $request)
    {
        // noop
    }

    public function invalidPayloadValue(Request $request, $value)
    {
        if (is_array($value)) {
            return count($value) < 1;
        } elseif (is_string($value)) {
            return trim($value) === '';
        }

        return is_null($value);
    }

    public function resolve(RestifyRequest $request, $filter)
    {
        $this->value = $filter;
    }

    /**
     * Get the URI key for the filter.
     *
     * @return string
     */
    public static function uriKey()
    {
        if (property_exists(static::class, 'uriKey') && is_string(static::$uriKey)) {
            return static::$uriKey;
        }

        $kebabWithoutFilter = Str::kebab(Str::replaceLast('Filter', '', class_basename(get_called_class())));

        return Str::plural($kebabWithoutFilter);
    }

    public function jsonSerialize()
    {
        return [
            'class' => static::class,
            'key' => static::uriKey(),
            'type' => $this->getType(),
            'options' => collect($this->options(app(Request::class)))->map(function ($value, $key) {
                return is_array($value) ? ($value + ['property' => $key]) : ['label' => $key, 'property' => $value];
            })->values()->all(),
        ];
    }
}
