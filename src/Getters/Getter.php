<?php

namespace Binaryk\LaravelRestify\Getters;

use Binaryk\LaravelRestify\Http\Requests\GetterRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Traits\AuthorizedToRun;
use Binaryk\LaravelRestify\Traits\AuthorizedToSee;
use Binaryk\LaravelRestify\Traits\Make;
use Binaryk\LaravelRestify\Traits\ProxiesCanSeeToGate;
use Binaryk\LaravelRestify\Traits\Visibility;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use JsonSerializable;
use ReturnTypeWillChange;
use Symfony\Component\HttpFoundation\Response;
use function tap;
use function throw_unless;
use Throwable;

/**
 * Class Getter
 *
 * @method Response|JsonResponse handle(RestifyRequest $request, ?Model $model = null)
 */
abstract class Getter implements JsonSerializable
{
    use Make;
    use AuthorizedToSee;
    use AuthorizedToRun;
    use ProxiesCanSeeToGate;
    use Visibility;

    /**
     * Default uri key for the getter.
     *
     * @var string
     */
    public static $uriKey;

    /**
     * The route action array.
     *
     * @var array
     */
    public $action;

    public static function indexQuery(RestifyRequest $request, $query): void
    {
        //
    }

    public function name(): string
    {
        return Restify::humanize($this);
    }

    public function uriKey(): string
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
     * @throws Throwable
     */
    public function handleRequest(GetterRequest $request): Response
    {
        throw_unless(method_exists($this, 'handle'), new Exception('Missing handle method from the getter.'));

        if ($request->isForRepositoryRequest()) {
            return $this->handle(
                $request,
                tap(
                    $request->modelQuery(),
                    fn (Builder $query) => static::indexQuery($request, $query)
                )->firstOrFail()
            );
        }

        return $this->handle($request);
    }

    public function withoutMiddleware(string|array $middleware): self
    {
        $this->action['excluded_middleware'] = array_merge(
            (array) ($this->action['excluded_middleware'] ?? []),
            Arr::wrap($middleware)
        );

        return $this;
    }

    public function excludedMiddleware(): array
    {
        return (array) ($this->action['excluded_middleware'] ?? []);
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return array_merge([
            'name' => $this->name(),
            'uriKey' => $this->uriKey(),
        ]);
    }
}
