<?php

namespace Binaryk\LaravelRestify\Tests\Prototypes;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Assertables\AssertableModel;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Binaryk\LaravelRestify\Traits\Make;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use JsonSerializable;

abstract class Prototypeable implements JsonSerializable
{
    use Make;

    public function __construct(
        public IntegrationTestCase $test,
    ) {}

    protected array $attributes = [];

    protected ?Model $model = null;

    public function fake(array $attributes = []): self
    {
        $this->attributes = static::modelClass()::factory($attributes)->make()->toArray();

        return $this;
    }

    public function attributes(array $attributes = []): self
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public static function modelClass(): string|Model
    {
        $model = Str::of(class_basename(get_called_class()))
            ->replaceLast('Prototype', '')
            ->trim()
            ->singular()
            ->__toString();

        if (class_exists($guessedClass = "Binaryk\\LaravelRestify\\Tests\Fixtures\\{$model}\\{$model}")) {
            return $guessedClass;
        }

        if (! isset(static::$modelClass)) {
            abort(502, '$modelClass is not defined.');
        }

        return static::$modelClass;
    }

    public static function repositoryClass(): string|Repository|null
    {
        if (class_exists($guessedClass = Restify::repositoryForModel(static::modelClass()))) {
            return $guessedClass;
        }

        if (! isset(static::$repositoryClass)) {
            return null;
        }

        return static::$repositoryClass;
    }

    public static function assertableClass(): string|AssertableModel|null
    {
        if (class_exists($guessedClass = '\\Binaryk\\LaravelRestify\\Tests\\Assertables\\Assertable'.static::baseModelClass())) {
            return $guessedClass;
        }

        if (! isset(static::$repositoryClass)) {
            return null;
        }

        return static::$assertableClass;
    }

    public static function baseModelClass(): string
    {
        return class_basename(static::modelClass());
    }

    private function ensureRepositoryClassDefined(): void
    {
        abort_unless((bool) static::repositoryClass(), 400, '$repositoryClass is not defined.');
    }

    private function ensureModelClassDefined(): void
    {
        abort_unless((bool) static::modelClass(), 400, '$modelClass is not defined.');
    }

    public function get(): TestResponse
    {
        $this->ensureRepositoryClassDefined();

        return $this->test->getJson(static::repositoryClass()::route());
    }

    public function create(?Closure $assertable = null, ?Closure $tap = null): self
    {
        $this->ensureRepositoryClassDefined();

        $id = $this->test->postJson(static::repositoryClass()::route(), $this->getAttributes())
            ->tap($tap ?? fn () => '')
            ->json('data.id');

        return $this->wirteableCallback($id, $assertable);
    }

    public function update(string|int|null $key = null, ?Closure $assertable = null, ?Closure $tap = null): self
    {
        $key = $key ?? $this->model()->getKey();

        $id = $this->test->postJson(static::repositoryClass()::route($key), $this->getAttributes())
            ->tap($tap ?? fn () => '')
            ->json('data.id');

        return $this->wirteableCallback($id, $assertable);
    }

    public function destroy(string|int|null $key = null, ?Closure $assertable = null, ?Closure $tap = null): self
    {
        $key = $key ?? $this->model()->getKey();

        $this->test
            ->deleteJson(static::repositoryClass()::route($key))
            ->tap($tap ?? fn () => '');

        return $this;
    }

    public function runAction(string $actionClass, array $payload = [], ?Closure $cb = null): self
    {
        abort_unless(is_subclass_of($actionClass, Action::class), 400, __('Invalid class instance.'));

        abort_unless((bool) static::repositoryClass(), 502, __('Undefined class $repositoryClass.'));

        $call = $this->test->postJson(static::repositoryClass()::action(
            $actionClass,
            $this->model()
                ? $this->model()->getKey()
                : null,
        ), $payload)->assertOk();

        if (is_callable($cb)) {
            $cb($call);
        }

        return $this;
    }

    protected function wirteableCallback(mixed $key, ?Closure $cb = null): self
    {
        if (is_null($key)) {
            return $this;
        }

        if (! static::modelClass() && is_callable($cb)) {
            $this->ensureModelClassDefined();
        }

        if (! static::modelClass()) {
            return $this;
        }

        $this->model = static::modelClass()::find($key);

        if (method_exists($this, 'setModel')) {
            $this->setModel($this->model());
        }

        if (is_callable($cb) && static::assertableClass()) {
            $cb(static::assertableClass()::make($this->model()));
        }

        return $this;
    }

    public function setModel(?Model $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function model()
    {
        return $this->model;
    }

    public function fresh(): Model
    {
        abort_unless((bool) $this->model(), 400, __('Model was not created.'));

        return $this->model()?->fresh();
    }

    public function assert(Closure $cb): self
    {
        abort_unless((bool) static::assertableClass(), 502, __('Undefined class $assertableClass.'));

        $cb(
            static::assertableClass()::make($this->fresh()),
        );

        return $this;
    }

    public function dd($prop = null)
    {
        dd($prop ? $this->{$prop} : $this);
    }

    public function ddd()
    {
        dd($this->jsonSerialize());
    }

    public function jsonSerialize(): array
    {
        return [];
    }
}
