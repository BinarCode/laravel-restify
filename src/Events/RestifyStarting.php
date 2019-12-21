<?php

namespace Binaryk\LaravelRestify\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RestifyStarting
{
    use Dispatchable;

    public $request;

    /**
     * RestifyServing constructor.
     * @param  Request  $request
     */
    public function __construct($request)
    {
        $this->request = $request;
    }
}
