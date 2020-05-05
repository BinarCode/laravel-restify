<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Http\Request;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
abstract class OrganicField extends BaseField
{

    /**
     * The callback used to authorize viewing the filter or action.
     *
     * @var \Closure|null
     */
    public $seeCallback;

    /**
     * Rules for applied when store.
     *
     * @var array
     */
    public $storingRules = [];

    /**
     * Rules for applied when update model.
     * @var array
     */
    public $updatingRules = [];

    /**
     * Rules for applied when store and update.
     *
     * @var array
     */
    public $rules = [];

    /**
     * @var array
     */
    public $messages = [];

    /**
     * Indicates if the element should be shown on the index view.
     *
     * @var \Closure|bool
     */
    public $showOnIndex = true;

    /**
     * Indicates if the element should be shown on the detail view.
     *
     * @var \Closure|bool
     */
    public $showOnDetail = true;

    /**
     * Specify that the element should be hidden from the detail view.
     *
     * @param \Closure|bool $callback
     * @return $this
     */
    public function showOnDetail($callback = true)
    {
        $this->showOnDetail = $callback;

        return $this;
    }

    /**
     * Specify that the element should be hidden from the detail view.
     *
     * @param \Closure|bool $callback
     * @return $this
     */
    public function showOnIndex($callback = true)
    {
        $this->showOnIndex = $callback;

        return $this;
    }

    /**
     * Specify that the element should be hidden from the detail view.
     *
     * @param \Closure|bool $callback
     * @return $this
     */
    public function hideFromDetail($callback = true)
    {
        $this->showOnDetail = is_callable($callback) ? function () use ($callback) {
            return ! call_user_func_array($callback, func_get_args());
        }
        : ! $callback;

        return $this;
    }

    /**
     * Specify that the element should be hidden from the index view.
     *
     * @param \Closure|bool $callback
     * @return $this
     */
    public function hideFromIndex($callback = true)
    {
        $this->showOnIndex = is_callable($callback) ? function () use ($callback) {
            return ! call_user_func_array($callback, func_get_args());
        }
        : ! $callback;

        return $this;
    }

    /**
     * Check showing on detail.
     *
     * @param RestifyRequest $request
     * @param mixed $repository
     * @return bool
     */
    public function isShownOnDetail(RestifyRequest $request, $repository): bool
    {
        if (is_callable($this->showOnDetail)) {
            $this->showOnDetail = call_user_func($this->showOnDetail, $request, $repository);
        }

        return $this->showOnDetail;
    }

    /**
     * Check hidden on detail.
     *
     * @param RestifyRequest $request
     * @param mixed $repository
     * @return bool
     */
    public function isHiddenOnDetail(RestifyRequest $request, $repository): bool
    {
        if (is_callable($this->showOnDetail)) {
            $this->showOnDetail = call_user_func($this->showOnDetail, $request, $repository);
        }

        return ! $this->showOnDetail;
    }

    /**
     * Check hidden on index.
     *
     * @param RestifyRequest $request
     * @param mixed $repository
     * @return bool
     */
    public function isHiddenOnIndex(RestifyRequest $request, $repository): bool
    {
        if (is_callable($this->showOnIndex)) {
            $this->showOnIndex = call_user_func($this->showOnIndex, $request, $repository);
        }

        return ! $this->showOnIndex;
    }

    /**
     * Determine if the element should be displayed for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function authorize(Request $request)
    {
        return $this->authorizedToSee($request);
    }

    /**
     * Determine if the filter or action should be available for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function authorizedToSee(Request $request)
    {
        return $this->seeCallback ? call_user_func($this->seeCallback, $request) : true;
    }

    /**
     * Set the callback to be run to authorize viewing the filter or action.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function canSee(Closure $callback)
    {
        $this->seeCallback = $callback;

        return $this;
    }
}
