<?php

namespace Binaryk\LaravelRestify\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * When this is listen we may inject auth routes
 *
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RestifyServiceProviderRegistered
{
    use Dispatchable;
}
