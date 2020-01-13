<?php

namespace Binaryk\LaravelRestify\Controllers;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

/**
 * Class RestResponse.
 *
 * @method RestResponse auth() 401
 * @method RestResponse refresh()
 * @method RestResponse created()
 * @method RestResponse deleted()
 * @method RestResponse blank()
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
class RestResponse implements Responsable
{
    public static $RESPONSE_DEFAULT_ATTRIBUTES = [
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
    const REST_RESPONSE_AUTH_CODE = 401;
    const REST_RESPONSE_REFRESH_CODE = 103;
    const REST_RESPONSE_CREATED_CODE = 201;
    const REST_RESPONSE_UPDATED_CODE = 200;
    const REST_RESPONSE_DELETED_CODE = 204; // update or delete with success
    const REST_RESPONSE_BLANK_CODE = 204;
    const REST_RESPONSE_ERROR_CODE = 500;
    const REST_RESPONSE_INVALID_CODE = 400;
    const REST_RESPONSE_UNAUTHORIZED_CODE = 401;
    const REST_RESPONSE_FORBIDDEN_CODE = 403;
    const REST_RESPONSE_MISSING_CODE = 404;
    const REST_RESPONSE_THROTTLE_CODE = 429;
    const REST_RESPONSE_SUCCESS_CODE = 200;
    const REST_RESPONSE_UNAVAILABLE_CODE = 503;

    /**
     * @var int
     */
    protected $code = self::REST_RESPONSE_SUCCESS_CODE;
    /**
     * @var int
     */
    protected $line;

    /**
     * The value of the attributes key MUST be an object (an “attributes object”).
     * Members of the attributes object (“attributes”) represent information
     * about the resource object in which it’s defined.
     *
     * @var array
     */
    protected $attributes;

    /**
     * Where specified, a meta member can be used to include non-standard meta-information.
     * The value of each meta member MUST be an object (a “meta object”).
     * @var array
     */
    protected $meta;

    /**
     * A links object containing links related to the resource.
     * @var
     */
    protected $links;

    /**
     * @var string
     */
    private $file;

    /**
     * @var string|null
     */
    private $stack;

    /**
     * @var array|null
     */
    private $errors;

    /**
     * @var array|null
     */
    private $data;

    /**
     * @var array
     */
    protected $headers;

    /**
     * @var string
     */
    protected $type;

    /**
     * Key of the newly created resource.
     * @var
     */
    protected $id;
    /**
     * Model related entities.
     * @var
     */
    protected $relationships;

    /**
     * RestResponse constructor.
     * @param  mixed  $content
     * @param  int  $status
     * @param  array  $headers
     */
    public function __construct($content = null, $status = 200, array $headers = [])
    {
        $this->data = $content;
        $this->code = $status;
        $this->headers = $headers;
    }

    /**
     * Set response data.
     *
     * @param  mixed  $data
     * @return $this|mixed
     */
    public function data($data = null)
    {
        if (func_num_args()) {
            $this->data = ($data instanceof Arrayable) ? $data->toArray() : $data;

            return $this;
        }

        return $this;
    }

    /**
     * Set response errors.
     *
     * @param  array  $errors
     * @return $this|null
     */
    public function errors(array $errors = null)
    {
        if (func_num_args()) {
            $this->errors = $errors;

            return $this;
        }

        return $this->errors;
    }

    /**
     * Add error to response errors.
     *
     * @param  mixed  $message
     * @return $this
     */
    public function addError($message)
    {
        if (! isset($this->errors)) {
            $this->errors = [];
        }

        $this->errors[] = $message;

        return $this;
    }

    /**
     * Set response Http code.
     *
     * @param  int  $code
     * @return $this|int
     */
    public function code($code = self::REST_RESPONSE_SUCCESS_CODE)
    {
        if (func_num_args()) {
            $this->code = $code;

            return $this;
        }

        return $this->code;
    }

    /**
     * Set response Http code.
     *
     * @param  int  $line
     * @return $this|int
     */
    public function line($line = null)
    {
        if (func_num_args()) {
            $this->line = $line;

            return $this;
        }

        return $this->line;
    }

    /**
     * @param  string  $file
     * @return $this|int
     */
    public function file(string $file = null)
    {
        if (func_num_args()) {
            $this->file = $file;

            return $this;
        }

        return $this->line;
    }

    /**
     * @param  string|null  $stack
     * @return $this|int
     */
    public function stack(string $stack = null)
    {
        if (func_num_args()) {
            $this->stack = $stack;

            return $this;
        }

        return $this->line;
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
     * @param $func
     * @param $args
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
     *
     * @return JsonResponse
     */
    public function respond($response = null)
    {
        if (! func_num_args()) {
            $response = new \stdClass();
            $response->data = new \stdClass();

            foreach ($this->fillable() as $property) {
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

        return $this->response()->json(static::beforeRespond($response), is_int($this->code()) ? $this->code() : self::REST_RESPONSE_SUCCESS_CODE, $this->headers);
    }

    /**
     * Set a root meta on response object.
     *
     * @param $name
     * @param $value
     * @return $this
     */
    public function setMeta($name, $value)
    {
        $this->meta[$name] = $value;

        return $this;
    }

    /**
     * Set a root meta on response object.
     *
     * @param $meta
     * @return $this
     */
    public function meta($meta)
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
     * @param $links
     * @return $this
     */
    public function links($links)
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
     * @param $name
     * @param $value
     * @return $this
     */
    public function setLink($name, $value)
    {
        $this->links[$name] = $value;

        return $this;
    }

    /**
     * Set message on response.
     * @param $message
     * @return RestResponse
     */
    public function message($message)
    {
        return $this->setMeta('message', $message);
    }

    /**
     * Get a response object root attribute.
     *
     * @param $name
     * @return mixed
     */
    public function getAttribute($name)
    {
        return $this->attributes[$name];
    }

    /**
     * Set attributes at root level.
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @return array
     */
    public function fillable(): array
    {
        return static::$RESPONSE_DEFAULT_ATTRIBUTES;
    }

    /**
     * @return ResponseFactory
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function response()
    {
        return app()->make(ResponseFactory::class);
    }

    /**
     * @param $name
     * @param $value
     * @return RestResponse
     */
    public function header($name, $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * @param $type
     * @return $this
     */
    public function type($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Useful when newly created repository, will prepare the response according
     * with JSON:API https://jsonapi.org/format/#document-resource-object-fields.
     *
     * @param  Repository  $repository
     * @param  bool  $withRelations
     * @return $this
     */
    public function forRepository(Repository $repository, $withRelations = false)
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

        if ($withRelations && $model instanceof RestifySearchable && $model::getWiths()) {
            foreach ($model::getWiths() as $k => $relation) {
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
    public function toResponse($request)
    {
        return $this->respond();
    }
}
