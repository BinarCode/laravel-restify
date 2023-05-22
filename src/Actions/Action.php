<?php

namespace Binaryk\LaravelRestify\Actions;

use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Models\Concerns\HasActionLogs;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Traits\AuthorizedToSee;
use Binaryk\LaravelRestify\Traits\Make;
use Binaryk\LaravelRestify\Traits\ProxiesCanSeeToGate;
use Binaryk\LaravelRestify\Traits\Visibility;
use Binaryk\LaravelRestify\Transaction;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonSerializable;
use ReturnTypeWillChange;

/**
 * Class Action
 *
 * @method JsonResponse handle(Request $request, ?Model|Collection $models = null, ?int $row = null)
 */
abstract class Action implements JsonSerializable
{
    use AuthorizedToSee;
    use ProxiesCanSeeToGate;
    use Make;
    use Visibility;

    /**
     * Number of models into a chunk when action for 'all'.
     */
    public static int $chunkCount = 200;

    /**
     * Indicated if this action don't require any models.
     */
    public bool $standalone = false;

    /**
     * Indicates if Restify should skip the field default update behavior in case it's actionable field.
     */
    public bool $skipFieldFill = true;

    /**
     * Default uri key for the action.
     *
     * @var string
     */
    public static $uriKey;

    public static function indexQuery(RestifyRequest $request, $query)
    {
        //
    }

    /**
     * The callback used to authorize running the action.
     */
    public ?Closure $runCallback;

    public function name()
    {
        return Restify::humanize($this);
    }

    /**
     * Get the URI key for the action.
     *
     * @return string
     */
    public function uriKey()
    {
        return static::$uriKey ?? Str::slug($this->name(), '-', null);
    }

    public static function guessUriKey(mixed $target): string
    {
        if ($target instanceof self) {
            return $target->uriKey();
        }

        return property_exists($target, 'uriKey')
            ? $target::$uriKey
            : Str::slug(Restify::humanize($target), '-', null);
    }

    /**
     * Determine if the action is executable for the given request.
     *
     * @param  Model  $model
     * @return bool
     */
    public function authorizedToRun(Request $request, $model)
    {
        return $this->runCallback ? call_user_func($this->runCallback, $request, $model) : true;
    }

    /**
     * Set the callback to be run to authorize running the action.
     *
     * @return $this
     */
    public function canRun(Closure $callback)
    {
        $this->runCallback = $callback;

        return $this;
    }

    /**
     * Get the payload available on the action.
     *
     *
     * @deprecated Use rules instead
     */
    public function payload(): array
    {
        return $this->rules();
    }

    /**
     * Validation rules to be applied before the action is called.
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Make current action being standalone. No model query will be performed.
     */
    public function standalone(bool $standalone = true): self
    {
        $this->standalone = $standalone;

        return $this;
    }

    /**
     * Check if the action is standalone.
     */
    public function isStandalone(): bool
    {
        return $this->standalone;
    }

//    abstract public function handle(ActionRequest $request, Collection $models): JsonResponse;

    public function handleRequest(ActionRequest $request)
    {
        if (! method_exists($this, 'handle')) {
            throw new Exception('Missing handle method from the action.');
        }

        if ($this->isStandalone()) {
            return Transaction::run(fn () => $this->handle($request));
        }

        $response = null;

        if (! $request->isForRepositoryRequest()) {
            $request->collectRepositories($this, static::$chunkCount, function ($models) use ($request, &$response) {
                Transaction::run(function () use ($models, $request, &$response) {
                    $response = $this->handle($request, $models);

                    $models->each(function (Model $model) {
                        //                        if (in_array(HasActionLogs::class, class_uses_recursive($model), true)) {
                        //                            Restify::actionLog()::forRepositoryAction($this, $model, $request->user())->save();
                        //                        }
                    });
                });
            });
        } else {
            Transaction::run(function () use ($request, &$response) {
                $response = $this->handle(
                    $request,
                    $model = tap($request->modelQuery(), function ($query) use ($request) {
                        static::indexQuery($request, $query);
                    })->firstOrFail()
                );
            });
        }

        return $response;
    }

    public function skipFieldFill(RestifyRequest $request): bool
    {
        return $this->skipFieldFill;
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return array_merge([
            'name' => $this->name(),
            'destructive' => $this instanceof DestructiveAction,
            'uriKey' => $this->uriKey(),
            'payload' => $this->payload(),
        ]);
    }
}
