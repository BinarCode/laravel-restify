<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Traits\Make;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use JsonSerializable;

abstract class Filter implements JsonSerializable
{
    use Make;
    use HasMode;

    public string $type = 'value';

    public $column;

    public $canSeeCallback;

    public static $uriKey;

    public $relatedRepositoryKey;

    public $relatedRepositoryTitle;

    public bool $advanced = false;

    public Repository $repository;

    public function __construct()
    {
        if ($this instanceof AdvancedFilter) {
            $this->setAdvanced();
        }

        if ($this instanceof SearchableFilter) {
            $this->type = SearchableFilter::TYPE;
        }

        if ($this instanceof SortableFilter) {
            $this->type = SortableFilter::TYPE;
        }

        if ($this instanceof MatchFilter) {
            $this->type = MatchFilter::TYPE;
        }

        $this->booted();
    }

    protected function booted()
    {
        //
    }

    abstract public function filter(RestifyRequest $request, Builder $query, $value);

    public function canSee(Closure $callback)
    {
        $this->canSeeCallback = $callback;

        return $this;
    }

    public function authorizedToSee(RestifyRequest $request): bool
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

    public function getQueryKey(): ?string
    {
        return Str::after($this->getColumn(), '.');
    }

    public function setColumn(string $column): self
    {
        $this->column = $column;

        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
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

    public function getRelatedRepositoryKey(): ?string
    {
        return $this->relatedRepositoryKey;
    }

    public function setRelatedRepositoryKey(string $repositoryKey): self
    {
        $this->relatedRepositoryKey = $repositoryKey;

        return $this;
    }

    public function setRelatedRepositoryTitle(string $title): self
    {
        $this->relatedRepositoryTitle = $title;

        return $this;
    }

    public function setRepository(Repository $repository): self
    {
        $this->repository = $repository;

        return $this;
    }

    public function setAdvanced(bool $advanced = true): self
    {
        $this->advanced = true;

        return $this;
    }

    public function getRelatedRepository(): ?array
    {
        return ($key = $this->getRelatedRepositoryKey())
            ? with(Restify::repositoryForKey($key), function ($repository = null) {
                if (is_subclass_of($repository, Repository::class)) {
                    return [
                        'key' => $repository::uriKey(),
                        'url' => Restify::path($repository::uriKey()),
                        'display_key' => $this->relatedRepositoryTitle ?? $repository::$title,
                        'label' => $repository::label(),
                    ];
                }
            })
            : null;
    }

    /**
     * Get the URI key for the filter.
     *
     * @return string
     */
    public static function uriKey(): string
    {
        if (property_exists(static::class, 'uriKey') && is_string(static::$uriKey)) {
            return static::$uriKey;
        }

        $kebabWithoutFilter = Str::kebab(Str::replaceLast('Filter', '', class_basename(get_called_class())));

        return Str::plural($kebabWithoutFilter);
    }

    public function isAdvanced(): bool
    {
        return $this instanceof AdvancedFilter;
    }

    public function jsonSerialize()
    {
        $serialized = with([
            'type' => $this->getType(),
            'advanced' => $this->advanced,
        ], function (array $initial) {
            return $this->relatedRepositoryKey ? array_merge($initial, [
                'repository' => $this->getRelatedRepository(),
            ]) : $initial;
        });

        if ($this->isAdvanced() === false) {
            $serialized = $serialized + ['column' => $this->getColumn()];
        }

        if ($this->isAdvanced()) {
            $serialized = array_merge($serialized, [
                'key' => static::uriKey(),
                'options' => method_exists($this, 'options')
                    ? collect($this->options(app(Request::class)))->map(function ($key, $value) {
                        return is_array($value) ? ($value + ['property' => $key]) : ['label' => $key, 'property' => $value];
                    })->values()->all()
                    : [],
            ]);
        }

        return $serialized;
    }

    public function dd(): self
    {
        dd($this);

        return $this;
    }
}
