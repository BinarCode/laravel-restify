<?php

namespace Binaryk\LaravelRestify\Traits;

use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Support\Arr;

trait ProxiesCanSeeToGate
{
    /**
     * Indicate that the entity can be seen when a given authorization ability is available.
     *
     * @return self
     */
    public function canSeeWhen(string $ability, null|array $arguments = [])
    {
        $arguments = Arr::wrap($arguments);

        if (isset($arguments[0]) && $arguments[0] instanceof Repository) {
            $arguments[0] = $arguments[0]->resource;
        }

        return $this->canSee(function ($request) use ($ability, $arguments) {
            return $request->user()->can($ability, $arguments);
        });
    }
}
