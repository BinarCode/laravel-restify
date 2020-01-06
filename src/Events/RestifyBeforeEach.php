<?php

namespace Binaryk\LaravelRestify\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RestifyBeforeEach
{
    use Dispatchable;

    public $request;

    /**
     * RestifyAfterEach constructor.
     * @param  $request
     */
    public function __construct($request)
    {
        $this->request = $request;
    }
}
