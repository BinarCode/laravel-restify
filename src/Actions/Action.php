<?php

namespace Binaryk\LaravelRestify\Actions;

use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Actions\JsonSchemaFromRulesAction;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Traits\AuthorizedToRun;
use Binaryk\LaravelRestify\Traits\AuthorizedToSee;
use Binaryk\LaravelRestify\Traits\Make;
use Binaryk\LaravelRestify\Traits\ProxiesCanSeeToGate;
use Binaryk\LaravelRestify\Traits\Visibility;
use Binaryk\LaravelRestify\Transaction;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\JsonSchema;
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
    use AuthorizedToRun;
    use AuthorizedToSee;
    use Make;
    use ProxiesCanSeeToGate;
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

    public static function indexQuery(RestifyRequest $request, $query)
    {
        //
    }

    /**
     * Action description, usually used in the UI or MCP.
     */
    public string $description = '';

    public function name()
    {
        return Restify::humanize($this);
    }

    public function description(RestifyRequest $request): string
    {
        return $this->description;
    }

    /**
     * Get the URI key for the action.
     */
    public function uriKey(): string
    {
        if (property_exists(static::class, 'uriKey') && is_string(static::$uriKey)) {
            return static::$uriKey;
        }

        return Str::slug($this->name(), '-', null);
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
            $this->authorizeRun($request, null);

            return Transaction::run(fn () => $this->handle($request));
        }

        $response = null;

        if (! $request->isForRepositoryRequest()) {
            Transaction::run(function () use ($request, &$response) {
                $request->collectRepositories($this, static::$chunkCount, function (Collection $models) use ($request, &$response) {
                    /** @var Collection<int, Model> $models */
                    foreach ($models as $model) {
                        $this->authorizeRun($request, $model);
                    }

                    $response = $this->handle($request, $models);

                    $models->each(function (Model $model) {
                        //                        if (in_array(HasActionLogs::class, class_uses_recursive($model), true)) {
                        //                            Restify::actionLog()::forRepositoryAction($this, $model, $request->user())->save();
                        //                        }
                    });
                });
            });
        } else {
            $model = tap($request->modelQuery(), function ($query) use ($request) {
                static::indexQuery($request, $query);
            })->firstOrFail();

            $this->authorizeRun($request, $model);

            Transaction::run(function () use ($model, $request, &$response) {
                $response = $this->handle($request, $model);
            });
        }

        return $response;
    }

    public function skipFieldFill(RestifyRequest $request): bool
    {
        return $this->skipFieldFill;
    }

    public function toolSchema(JsonSchema $schema): array
    {
        return app(JsonSchemaFromRulesAction::class)($schema, $this->rules());
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return array_merge([
            'name' => $this->name(),
            'description' => $this->description(app(RestifyRequest::class)),
            'destructive' => $this instanceof DestructiveAction,
            'uriKey' => $this->uriKey(),
            'payload' => $this->payload(),
        ]);
    }
}
