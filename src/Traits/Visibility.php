<?php

namespace Binaryk\LaravelRestify\Traits;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

trait Visibility
{
    public bool $showOnIndex = true;

    public bool $showOnShow = true;

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
        return false === $this->isShownOnShow($request, $repository);
    }

    public function isShownOnIndex(RestifyRequest $request, $repository): bool
    {
        return false === $this->isHiddenOnIndex($request, $repository);
    }

    public function isHiddenOnIndex(RestifyRequest $request, $repository): bool
    {
        if (is_callable($this->showOnIndex)) {
            $this->showOnIndex = call_user_func($this->showOnIndex, $request, $repository);
        }

        return ! $this->showOnIndex;
    }
}
