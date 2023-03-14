<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Repositories\RepositoryCollection;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Throwable;

/**
 * Class RestResponse.
 *
 * @method RestResponse auth() 401
 * @method RestResponse refresh()
 * @method RestResponse deleted()
 * @method RestResponse blank()
 * @method RestResponse notFound()
 * @method RestResponse error() 500
 * @method RestResponse invalid() 400
 * @method RestResponse unauthorized() 401 - don't have correct password/email
 * @method RestResponse forbidden() 403 - don't have enough permissions
 * @method RestResponse missing() 404
 * @method RestResponse success()
 * @method RestResponse unavailable()
 * @method RestResponse throttle() 429 - too many attempts
 *
 * @author lupacescueduard <eduard.lupacescu@binarcode.com>
 */
class RestResponse extends JsonResponse implements Responsable
{
    public static $RESPONSE_DEFAULT_ATTRIBUTES = [
        'attributes',
        'line',
        'file',
        'stack',
        'data',
        'meta',
        'links',
        'errors',
    ];

    /**
     * Response Codes.
     */
    public const REST_RESPONSE_AUTH_CODE = 401;

    public const REST_RESPONSE_REFRESH_CODE = 103;

    public const REST_RESPONSE_CREATED_CODE = 201;

    public const REST_RESPONSE_UPDATED_CODE = 200;

    public const REST_RESPONSE_DELETED_CODE = 204; // update or delete with success

    public const REST_RESPONSE_BLANK_CODE = 204;

    public const REST_RESPONSE_ERROR_CODE = 500;

    public const REST_RESPONSE_INVALID_CODE = 400;

    public const REST_RESPONSE_UNAUTHORIZED_CODE = 401;

    public const REST_RESPONSE_FORBIDDEN_CODE = 403;

    public const REST_RESPONSE_MISSING_CODE = 404;

    public const REST_RESPONSE_NOTFOUND_CODE = 404;

    public const REST_RESPONSE_THROTTLE_CODE = 429;

    public const REST_RESPONSE_SUCCESS_CODE = 200;

    public const REST_RESPONSE_UNAVAILABLE_CODE = 503;

    public const CODES = [
        self::REST_RESPONSE_AUTH_CODE,
        self::REST_RESPONSE_REFRESH_CODE,
        self::REST_RESPONSE_CREATED_CODE,
        self::REST_RESPONSE_UPDATED_CODE,
        self::REST_RESPONSE_DELETED_CODE,
        self::REST_RESPONSE_BLANK_CODE,
        self::REST_RESPONSE_ERROR_CODE,
        self::REST_RESPONSE_INVALID_CODE,
        self::REST_RESPONSE_UNAUTHORIZED_CODE,
        self::REST_RESPONSE_FORBIDDEN_CODE,
        self::REST_RESPONSE_MISSING_CODE,
        self::REST_RESPONSE_NOTFOUND_CODE,
        self::REST_RESPONSE_THROTTLE_CODE,
        self::REST_RESPONSE_SUCCESS_CODE,
        self::REST_RESPONSE_UNAVAILABLE_CODE,
    ];

    /**
     * The value of the attributes key MUST be an object (an “attributes object”).
     * Members of the attributes object (“attributes”) represent information
     * about the resource object in which it’s defined.
     */
    protected array $attributes;

    /**
     * Where specified, a meta member can be used to include non-standard meta-information.
     * The value of each meta member MUST be an object (a “meta object”).
     *
     * @var array
     */
    protected ?array $meta = null;

    /**
     * A links object containing links related to the resource.
     */
    protected array $links;

    protected string $type;

    protected int $code = 200;

    protected ?int $line;

    private ?string $file;

    private ?string $stack;

    private array $errors = [];

    /**
     * Key of the newly created resource.
     */
    protected $id;

    /**
     * Model related entities.
     */
    protected $relationships;

    /**
     * Indicate if response could include sensitive information (file, line).
     */
    public bool $debug = false;

    public function data($data = null): self
    {
        if (func_num_args()) {
            $data = ($data instanceof Arrayable) ? $data->toArray() : $data;

            $this->setData(compact('data'));

            return $this;
        }

        return $this;
    }

    /**
     * Set response errors.
     *
     * @param  mixed  $errors
     * @return $this|null
     */
    public function errors($errors): self
    {
        $this->errors = Arr::wrap($errors);

        return $this;
    }

    /**
     * Add error to response errors.
     *
     * @param  mixed  $message
     * @return $this
     */
    public function addError($message): self
    {
        $this->errors[] = $message;

        return $this;
    }

    public function code($code = self::REST_RESPONSE_SUCCESS_CODE): self
    {
        $this->code = $code;

        return $this;
    }

    public function line($line): self
    {
        $this->line = $line;

        return $this;
    }

    public function file(string $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function stack(string $stack): self
    {
        $this->stack = $stack;

        return $this;
    }

    /**
     * Magic to get response code constants.
     *
     * @param  string  $key
     * @return mixed|null
     */
    public function __get($key)
    {
        if (isset($this->$key)) {
            return $this->$key;
        }

        $code = 'static::REST_RESPONSE_'.strtoupper($key).'_CODE';

        return defined($code) ? constant($code) : null;
    }

    /**
     * Magic to allow setting the response
     * code in method chaining.
     *
     * @return $this|int|mixed|RestResponse
     */
    public function __call($func, $args)
    {
        $code = 'static::REST_RESPONSE_'.strtoupper($func).'_CODE';

        if (defined($code)) {
            return $this->code(constant($code));
        }

        return call_user_func_array($func, $args);
    }

    /**
     * Build a new response with our response data.
     *
     * @param  mixed  $response
     */
    public function respond($response = null): JsonResponse
    {
        if (! func_num_args()) {
            $response = new \stdClass();
            $response->data = new \stdClass();

            foreach (static::$RESPONSE_DEFAULT_ATTRIBUTES as $property) {
                if (isset($this->{$property})) {
                    $response->{$property} = $this->{$property};
                }
            }

            //according with https://jsonapi.org/format/#document-top-level these fields should be in data:
            foreach (['attributes', 'relationships', 'type', 'id'] as $item) {
                if (isset($this->{$item})) {
                    $response->data->{$item} = $this->{$item};
                }
            }
        }

        return tap(response()->json(static::beforeRespond($response), is_int($this->code()) ? $this->code() : self::REST_RESPONSE_SUCCESS_CODE), function ($response) {
            $this->withResponse($response, request());
        });
    }

    /**
     * Set a root meta on response object.
     *
     * @return $this
     */
    public function setMeta($name, $value): self
    {
        $this->meta[$name] = $value;

        return $this;
    }

    /**
     * Set a root meta on response object.
     *
     * @return $this
     */
    public function meta($meta): self
    {
        if (func_num_args()) {
            $this->meta = ($meta instanceof Arrayable) ? $meta->toArray() : $meta;

            return $this;
        }

        return $this;
    }

    /**
     * Set a root meta on response object.
     *
     * @return $this
     */
    public function links($links): self
    {
        if (func_num_args()) {
            $this->links = ($links instanceof Arrayable) ? $links->toArray() : $links;

            return $this;
        }

        return $this;
    }

    /**
     * Set a root link on response object.
     *
     * @return $this
     */
    public function setLink($name, $value): self
    {
        $this->links[$name] = $value;

        return $this;
    }

    /**
     * Set message on response.
     */
    public function message($message): self
    {
        return $this->setMeta('message', $message);
    }

    /**
     * Get a response object root attribute.
     *
     * @return mixed
     */
    public function getAttribute($name)
    {
        return $this->attributes[$name];
    }

    /**
     * Set attributes at root level.
     *
     * @return mixed
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Set "id" at root level for a model.
     *
     * @return mixed
     */
    public function id($id): self
    {
        $this->id = $id;

        return $this;
    }

    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Useful when newly created repository, will prepare the response according
     * with JSON:API https://jsonapi.org/format/#document-resource-object-fields.
     *
     * @param  bool  $withRelations
     * @return $this
     */
    public function forRepository(Repository $repository, $withRelations = false): self
    {
        $model = $repository->model();

        if (false === $model instanceof Model) {
            return $this;
        }

        if (is_null($model->getKey())) {
            return $this;
        }
        $this->type($repository::uriKey());
        $this->setAttributes($model->attributesToArray());
        $this->id = $model->getKey();

        if ($withRelations && $model instanceof RestifySearchable && $model::withs()) {
            foreach ($model::withs() as $k => $relation) {
                if ($model->relationLoaded($relation)) {
                    $this->relationships[$relation] = $model->{$relation}->get();
                }
            }
        }

        return $this;
    }

    public static function beforeRespond($response)
    {
        //The members data and errors MUST NOT coexist in the same document. - https://jsonapi.org/format/#introduction
        if (isset($response->errors)) {
            unset($response->data);

            return $response;
        }

        if (isset($response->data)) {
            unset($response->errors);

            return $response;
        }

        return $response;
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request = null)
    {
        if ($this->errors) {
            $this->setData([
                'errors' => $this->getErrors(),
            ]);
        }

        if ($this->code) {
            if (in_array($this->code, static::CODES)) {
                $this->setStatusCode(is_int($this->code) ? $this->code : self::REST_RESPONSE_SUCCESS_CODE);
            }
        }

        if ($this->debug) {
            $extra = [];

            foreach (['line', 'errors', 'file', 'stack', 'meta'] as $property) {
                if (isset($this->{$property})) {
                    $extra[$property] = $this->{$property};
                }
            }

            $this->setData($extra);
        }

        if ($this->meta) {
            $this->original['meta'] = $this->meta;
            $this->setData($this->original);
        }

        // Single resource ($this->model(...))
        if ($this->id) {
            $original = [
                'data' => [
                    'attributes' => $this->attributes,
                    'type' => $this->type,
                    'id' => $this->id,
                ],
            ];

            $this->setData($original);
        }

        return $this;
    }

    public function withResponse($response, $request)
    {
        //
    }

    public function getErrors(): ?array
    {
        return $this->errors instanceof Arrayable ? $this->errors->toArray() : $this->errors;
    }

    /**
     * @return $this
     */
    public function dump(Throwable $exception, $condition)
    {
        if ($condition) {
            $this->debug = true;

            $this->line($exception->getLine())
                ->code($exception->getCode())
                ->file($exception->getFile())
                ->errors($exception->getMessage())
                ->stack($exception->getTraceAsString());
        }

        return $this;
    }

    /**
     * Debug the log if the environment is local.
     *
     * @return $this
     */
    public function dumpLocal(Throwable $exception): self
    {
        return $this->dump($exception, App::environment('production') === false);
    }

    /**
     * Set the JSON:API format for a single resource.
     *
     * $this->model( User::find(1) )
     *
     * @return $this
     */
    public function model(Model $model): self
    {
        $this->setAttributes($model->jsonSerialize())
            ->type($model->getTable())
            ->id($model->getKey());

        return $this;
    }

    public static function created()
    {
        return (new self())->code(201);
    }

    public static function index(AbstractPaginator|Paginator $paginator, array $meta = []): JsonResponse
    {
        return response()->json(
            [
                'meta' => array_merge(RepositoryCollection::meta($paginator->toArray()), $meta),
                'links' => RepositoryCollection::paginationLinks($paginator->toArray()),
                'data' => $paginator->getCollection(),
            ]
        );
    }
}
