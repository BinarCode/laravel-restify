<?php

namespace Binaryk\LaravelRestify\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RestifyStarting
{
    use Dispatchable;

    public $request;

    /**
     * RestifyServing constructor.
     */
    public function __construct($request)
    {
        $this->request = $request;
    }
}
