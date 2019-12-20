<?php

namespace Binaryk\LaravelRestify\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

/**
 * @package Binaryk\LaravelRestify\Events;
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RestifyServing
{
    use Dispatchable;

    public $request;

    /**
     * RestifyServing constructor.
     * @param  Request  $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
