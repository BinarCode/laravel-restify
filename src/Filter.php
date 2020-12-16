<?php

namespace Binaryk\LaravelRestify;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Traits\Make;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use JsonSerializable;

abstract class Filter implements JsonSerializable
{
    use Make;

    public $type = 'value';

    public $column;

    public $value;

    public $canSeeCallback;

    public static $uriKey;

    public $relatedRepositoryKey;

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

    public function getColumn(): ?string
    {
        return $this->column;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
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

    public function getRelatedRepositoryKey(): ?string
    {
        return $this->relatedRepositoryKey;
    }

    public function setRelatedRepositoryKey(string $repositoryKey): self
    {
        $this->relatedRepositoryKey = $repositoryKey;

        return $this;
    }

    public function getRelatedRepositoryUrl(): ?string
    {
        return ($key = $this->getRelatedRepositoryKey())
            ? with(Restify::repositoryForKey($key), function ($repository = null) {
                if (is_subclass_of($repository, Repository::class)) {
                    return Restify::path($repository::uriKey());
                }
            })
            : null;
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
        return with([
            'class' => static::class,
            'key' => static::uriKey(),
            'type' => $this->getType(),
            'column' => $this->getColumn(),
            'options' => collect($this->options(app(Request::class)))->map(function ($value, $key) {
                return is_array($value) ? ($value + ['property' => $key]) : ['label' => $key, 'property' => $value];
            })->values()->all(),
        ], function (array $initial) {
            return $this->relatedRepositoryKey
                ? array_merge($initial, [
                    'related_repository_key' => $this->getRelatedRepositoryKey(),
                    'related_repository_url' => $this->getRelatedRepositoryUrl(),
                ])
                : $initial;
        });
    }
}
