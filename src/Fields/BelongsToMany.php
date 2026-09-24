<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Fields\Concerns\Attachable;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Binaryk\LaravelRestify\Repositories\PivotsCollection;
use Binaryk\LaravelRestify\Repositories\Repository;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

class BelongsToMany extends EagerField
{
    use Attachable;

    /**
     * Callback used to attach.
     *
     * @var Closure
     */
    public $attachCallback;

    /**
     * Callback used to detach.
     *
     * @var Closure
     */
    public $detachCallback;

    public function __construct($relation, ?string $parentRepository = null)
    {
        parent::__construct($relation, $parentRepository);

        $this->readonly();
    }

    public function resolve($repository, $attribute = null)
    {
        /**
         * @var Repository $repository
         */
        /** @var class-string<Repository> $repositoryClass */
        $repositoryClass = $this->repositoryClass;

        if ($repository->model()->relationLoaded($this->relation)) {
            $paginator = $repository->model()->getRelation($this->relation);
        } else {
            $paginator = $repository->{$this->relation}();

            $paginator = $paginator->take($this->relatablePerPage($repository::$defaultRelatablePerPage))->get();
        }

        $this->value = $paginator->map(function (Model $item) use ($repositoryClass) {
            try {
                /**
                 * @var Repository $repositoryFromClass
                 */
                $repositoryFromClass = $repositoryClass::resolveWith($item);

                return $repositoryFromClass
                    ->allowToShow(
                        $this->detectMcpRequest() ? app(McpRequest::class) : app(RestifyRequest::class)
                    )
                    ->withPivots(
                        PivotsCollection::make($this->pivotFields)
                            ->map(fn (Field $field) => clone $field)
                            ->filter(fn (Field $field) => ! $field->isHidden(app(RestifyRequest::class)))
                            ->resolveFromPivot($item->getRelation('pivot'))
                    )
                    ->eager($this);
            } catch (AuthorizationException) {
                return null;
            }
        });

        return $this;
    }

    public function attachCallback(callable|Closure $callback)
    {
        $this->attachCallback = $callback;

        return $this;
    }

    public function detachCallback(callable|Closure $callback)
    {
        $this->detachCallback = $callback;

        return $this;
    }
}
