<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures;

use Binaryk\LaravelRestify\Resource;
use Illuminate\Http\Request;

/**
 * @package Binaryk\LaravelRestify\Tests\Fixtures;
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class UserResource extends Resource
{

    public static $model = User::class;

    /**
     * @inheritDoc
     */
    public function fields(Request $request)
    {
        return [];
    }

    /**
     * Get the URI key for the resource.
     *
     * @return string
     */
    public static function uriKey()
    {
        return 'users';
    }
}
