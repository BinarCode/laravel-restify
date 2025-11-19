<?php

namespace Binaryk\LaravelRestify\Traits;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

trait Visibility
{
    public bool $showOnIndex = true;

    public bool $showOnShow = true;

    public bool $showOnMcp = true;

    public function showOnShow($callback = true)
    {
        $this->showOnShow = $callback;

        return $this;
    }

    public function showOnIndex($callback = true)
    {
        $this->showOnIndex = $callback;

        return $this;
    }

    public function onlyOnShow($value = true)
    {
        $this->showOnIndex = ! $value;
        $this->showOnShow = $value;

        return $this;
    }

    public function onlyOnIndex($value = true)
    {
        $this->showOnIndex = $value;
        $this->showOnShow = ! $value;

        return $this;
    }

    public function hideFromShow($callback = true)
    {
        $this->showOnShow = is_callable($callback) ? function () use ($callback) {
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
        if (is_callable($this->showOnShow)) {
            $this->showOnShow = call_user_func($this->showOnShow, $request, $repository);
        }

        return $this->showOnShow;
    }

    public function isHiddenOnShow(RestifyRequest $request, $repository): bool
    {
        return $this->isShownOnShow($request, $repository) === false;
    }

    public function isShownOnIndex(RestifyRequest $request, $repository): bool
    {
        return $this->isHiddenOnIndex($request, $repository) === false;
    }

    public function isHiddenOnIndex(RestifyRequest $request, $repository): bool
    {
        if (is_callable($this->showOnIndex)) {
            $this->showOnIndex = call_user_func($this->showOnIndex, $request, $repository);
        }

        return ! $this->showOnIndex;
    }

    public function showOnMcp($callback = true)
    {
        $this->showOnMcp = $callback;

        return $this;
    }

    public function onlyOnMcp($value = true)
    {
        $this->showOnIndex = ! $value;
        $this->showOnShow = ! $value;
        $this->showOnMcp = $value;

        return $this;
    }

    public function hideFromMcp($callback = true)
    {
        $this->showOnMcp = is_callable($callback) ? function () use ($callback) {
            return ! call_user_func_array($callback, func_get_args());
        }
        : ! $callback;

        return $this;
    }

    public function isShownOnMcp(RestifyRequest $request, $repository): bool
    {
        if (is_callable($this->showOnMcp)) {
            $this->showOnMcp = call_user_func($this->showOnMcp, $request, $repository);
        }

        return $this->showOnMcp;
    }

    public function isHiddenFromMcp(RestifyRequest $request, $repository): bool
    {
        return $this->isShownOnMcp($request, $repository) === false;
    }
}
