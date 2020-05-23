<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Closure;

class ProfileAvatarRequest extends RestifyRequest
{
    /**
     * @var Closure
     */
    public static $pathCallback;

    /**
     * @var string
     */
    public static string $path = 'avatars';

    /**
     * @var string
     */
    public static string $userAvatarAttribute = 'avatar';

    public static function usingPath(callable $pathCallback)
    {
        static::$pathCallback = $pathCallback;
    }

}
