<?php

namespace Binaryk\LaravelRestify\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RestifyBeforeEach
{
    use Dispatchable;

    public $request;

    /**
     * RestifyAfterEach constructor.
     */
    public function __construct($request)
    {
        $this->request = $request;
    }
}
