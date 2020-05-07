<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Closure;
use Illuminate\Http\Request;

abstract class OrganicField extends BaseField
{
    public $seeCallback;

    public $storingRules = [];

    public $updatingRules = [];

    public $rules = [];

    public $messages = [];

    public $showOnIndex = true;

    public $showOnDetail = true;

    public function showOnDetail($callback = true)
    {
        $this->showOnDetail = $callback;

        return $this;
    }

    public function showOnShow($callback = true)
    {
        return $this->showOnDetail($callback);
    }

    public function showOnIndex($callback = true)
    {
        $this->showOnIndex = $callback;

        return $this;
    }

    public function hideFromDetail($callback = true)
    {
        $this->showOnDetail = is_callable($callback) ? function () use ($callback) {
            return ! call_user_func_array($callback, func_get_args());
        }
        : ! $callback;

        return $this;
    }

    public function hideFromIndex($callback = true)
    {
        $this->showOnIndex = is_callable($callback) ? function () use ($callback) {
            return ! call_user_func_array($callback, func_get_args());
        }
        : ! $callback;

        return $this;
    }

    public function isShownOnShow(RestifyRequest $request, $repository): bool
    {
        return $this->isShownOnDetail($request, $repository);
    }

    public function isShownOnDetail(RestifyRequest $request, $repository): bool
    {
        if (is_callable($this->showOnDetail)) {
            $this->showOnDetail = call_user_func($this->showOnDetail, $request, $repository);
        }

        return $this->showOnDetail;
    }

    public function isHiddenOnDetail(RestifyRequest $request, $repository): bool
    {
        return false === $this->isShownOnDetail($request, $repository);
    }

    public function isHiddenOnIndex(RestifyRequest $request, $repository): bool
    {
        if (is_callable($this->showOnIndex)) {
            $this->showOnIndex = call_user_func($this->showOnIndex, $request, $repository);
        }

        return ! $this->showOnIndex;
    }

    public function isShownOnIndex(RestifyRequest $request, $repository): bool
    {
        return false === $this->isHiddenOnIndex($request, $repository);
    }

    public function isShownOnUpdate(RestifyRequest $request, $repository): bool
    {
        return $this->authorize($request);
    }

    public function isShownOnStore(RestifyRequest $request, $repository): bool
    {
        return $this->authorize($request);
    }

    public function authorize(Request $request)
    {
        return $this->authorizedToSee($request);
    }

    public function authorizedToSee(Request $request)
    {
        return $this->seeCallback ? call_user_func($this->seeCallback, $request) : true;
    }

    public function canSee(Closure $callback)
    {
        $this->seeCallback = $callback;

        return $this;
    }
}
