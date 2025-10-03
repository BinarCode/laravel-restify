<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpRequestable;
use Binaryk\LaravelRestify\Traits\ProxiesCanSeeToGate;
use Closure;
use Illuminate\Http\Request;

abstract class OrganicField extends BaseField
{
    use ProxiesCanSeeToGate;

    public $canSeeCallback;

    public $canUpdateCallback;

    public $canPatchCallback;

    public $canUpdateBulkCallback;

    public $canStoreCallback;

    public $readonlyCallback;

    public $hiddenCallback;

    public array $rules = [];

    public array $storingRules = [];

    public array $storingBulkRules = [];

    public array $updateBulkRules = [];

    public array $updatingRules = [];

    public array $messages = [];

    public $showOnIndex = true;

    public $showOnShow = true;

    public $showOnMcp = true;

    public $hideFromMcpCallback;

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

    public function showOnMcp($callback = true)
    {
        $this->showOnMcp = $callback;

        return $this;
    }

    public function hideFromMcp($callback = true)
    {
        $this->hideFromMcpCallback = $callback;

        return $this;
    }

    public function isShownOnShow(RestifyRequest $request, $repository): bool
    {
        if ($this->isHidden($request)) {
            return false;
        }

        // Check MCP-specific visibility for MCP requests
        if ($request instanceof McpRequestable) {
            return $this->isShownOnMcp($request, $repository);
        }

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
        if ($this->isHidden($request)) {
            return false;
        }

        // Check MCP-specific visibility for MCP requests
        if ($request instanceof McpRequestable) {
            return $this->isShownOnMcp($request, $repository);
        }

        return $this->isHiddenOnIndex($request, $repository) === false;
    }

    public function isHiddenOnIndex(RestifyRequest $request, $repository): bool
    {
        if (is_callable($this->showOnIndex)) {
            $this->showOnIndex = call_user_func($this->showOnIndex, $request, $repository);
        }

        return ! $this->showOnIndex;
    }

    public function isShownOnMcp(RestifyRequest $request, $repository): bool
    {
        if ($this->isHidden($request)) {
            return false;
        }

        if ($this->isHiddenFromMcp($request, $repository)) {
            return false;
        }

        if (is_callable($this->showOnMcp)) {
            return call_user_func($this->showOnMcp, $request, $repository);
        }

        return $this->showOnMcp;
    }

    public function isHiddenFromMcp(RestifyRequest $request, $repository): bool
    {
        return with($this->hideFromMcpCallback, function ($callback) use ($request, $repository) {
            if ($callback === true || (is_callable($callback) && call_user_func($callback, $request, $repository))) {
                return true;
            }

            return false;
        });
    }

    public function authorize(Request $request)
    {
        return $this->authorizedToSee($request);
    }

    public function authorizedToSee(Request $request)
    {
        return $this->canSeeCallback ? call_user_func($this->canSeeCallback, $request) : true;
    }

    public function authorizedToUpdate(Request $request)
    {
        return $this->canUpdateCallback ? call_user_func($this->canUpdateCallback, $request) : true;
    }

    public function authorizedToPatch(Request $request)
    {
        return $this->canPatchCallback ? call_user_func($this->canPatchCallback, $request) : true;
    }

    public function authorizedToUpdateBulk(Request $request)
    {
        return $this->canUpdateBulkCallback ? call_user_func($this->canUpdateBulkCallback, $request) : true;
    }

    public function authorizedToStore(Request $request)
    {
        return $this->canStoreCallback ? call_user_func($this->canStoreCallback, $request) : true;
    }

    public function canSee(Closure $callback)
    {
        $this->canSeeCallback = $callback;

        return $this;
    }

    public function canUpdate(Closure $callback)
    {
        $this->canUpdateCallback = $callback;

        return $this;
    }

    public function canPatch(Closure $callback)
    {
        $this->canPatchCallback = $callback;

        return $this;
    }

    public function canUpdateBulk(Closure $callback)
    {
        $this->canUpdateBulkCallback = $callback;

        return $this;
    }

    public function canStore(Closure $callback)
    {
        $this->canStoreCallback = $callback;

        return $this;
    }

    public function readonly($callback = true)
    {
        $this->readonlyCallback = $callback;

        return $this;
    }

    public function isReadonly(RestifyRequest $request)
    {
        return with($this->readonlyCallback, function ($callback) use ($request) {
            if ($callback === true || (is_callable($callback) && call_user_func($callback, $request))) {
                return true;
            }

            return false;
        });
    }

    public function isShownOnUpdate(RestifyRequest $request, $repository): bool
    {
        return ! $this->isReadonly($request);
    }

    public function isShownOnUpdateBulk(RestifyRequest $request, $repository): bool
    {
        return ! $this->isReadonly($request);
    }

    public function isShownOnStore(RestifyRequest $request, $repository): bool
    {
        return ! $this->isReadonly($request);
    }

    public function isShownOnStoreBulk(RestifyRequest $request, $repository): bool
    {
        return ! $this->isReadonly($request);
    }

    public function isHidden(RestifyRequest $request)
    {
        return with($this->hiddenCallback, function ($callback) use ($request) {
            if ($callback === true || (is_callable($callback) && call_user_func($callback, $request))) {
                return true;
            }

            return false;
        });
    }
}
