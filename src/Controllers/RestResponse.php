<?php

namespace Binaryk\LaravelRestify\Controllers;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;

/**
 * Class RestResponse.
 *
 * @method RestResponse auth
 * @method RestResponse refresh
 * @method RestResponse created
 * @method RestResponse deleted
 * @method RestResponse blank
 * @method RestResponse error
 * @method RestResponse invalid
 * @method RestResponse forbidden
 * @method RestResponse missing
 * @method RestResponse success
 * @method RestResponse unavailable
 *
 * @author lupacescueduard <eduard.lupacescu@binarcode.com>
 */
class RestResponse
{
    /**
     * Response Codes.
     */
    const REST_RESPONSE_AUTH_CODE = 401;
    const REST_RESPONSE_REFRESH_CODE = 103;
    const REST_RESPONSE_CREATED_CODE = 201;
    const REST_RESPONSE_UPDATED_CODE = 201;
    const REST_RESPONSE_DELETED_CODE = 204;
    const REST_RESPONSE_BLANK_CODE = 204;
    const REST_RESPONSE_ERROR_CODE = 500;
    const REST_RESPONSE_INVALID_CODE = 400;
    const REST_RESPONSE_FORBIDDEN_CODE = 403;
    const REST_RESPONSE_MISSING_CODE = 404;
    const REST_RESPONSE_SUCCESS_CODE = 200;
    const REST_RESPONSE_UNAVAILABLE_CODE = 503;

    /**
     * @var int
     */
    protected $code = self::REST_RESPONSE_SUCCESS_CODE;

    /**
     * Attributes to be appended to response at root level.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Set response resource total.
     *
     * @param int $total
     * @return $this|int
     */
    public function total($total = null)
    {
        if (func_num_args()) {
            $this->total = (int) $total;

            return $this;
        }

        return $this->total;
    }

    /**
     * Set response resource current page.
     *
     * @param int $currentPage
     * @param int $lastPage
     * @return $this|int
     */
    public function paginate($currentPage = 0, $lastPage = 0)
    {
        if (func_num_args()) {
            $this->currentPage = (int) $currentPage;
            $this->lastPage = (int) $lastPage;
        }

        return $this;
    }

    /**
     * Set response resource last page.
     *
     * @param int $total
     * @return $this|int
     */
    public function lastPage($total = null)
    {
        if (func_num_args()) {
            $this->total = (int) $total;

            return $this;
        }

        return $this->total;
    }

    /**
     * Set response data.
     *
     * @param mixed $data
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
     * Set response aggregations.
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function aggregations(array $aggregations = null)
    {
        $this->aggregations = $aggregations;

        return $this;
    }

    /**
     * Set response errors.
     *
     * @param array $errors
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
     * @param mixed $message
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
     * Set response http code.
     *
     * @param int $code
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
     * Magic to get response code constants.
     *
     * @param string $key
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
     * @param mixed $response
     *
     * @return JsonResponse
     */
    public function respond($response = null)
    {
        if (! func_num_args()) {
            $response = new \stdClass();

            foreach (['total', 'data', 'aggregations', 'errors', 'lastPage', 'currentPage'] as $property) {
                if (isset($this->{$property})) {
                    $response->{$property} = $this->{$property};
                }
            }

            foreach ($this->attributes as $attribute => $value) {
                $response->{$attribute} = $value;
            }

            foreach ($response as $property => $value) {
                if ($value instanceof Arrayable) {
                    $response->{$property} = $value->toArray();
                }
            }
        }

        return response()->json($response, $this->code());
    }

    /**
     * Set a root attribute on response object.
     *
     * @param $name
     * @param $value
     * @return $this
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * Set message on response.
     * @param $message
     * @return RestResponse
     */
    public function message($message)
    {
        return $this->setAttribute('message', $message);
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
}
