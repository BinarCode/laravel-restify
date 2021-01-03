<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Closure;

class ProfileAvatarRequest extends RestifyRequest
{
    /**
     * @var Closure
     */
    public static $pathCallback;

    public static string $path = 'avatars';

    public static string $disk = 'public';

    /**
     * @var string
     */
    public static string $userAvatarAttribute = 'avatar';

    public static function usingPath(callable $pathCallback)
    {
        static::$pathCallback = $pathCallback;
    }

    public static function usingDisk(string $disk = 'public')
    {
        static::$disk = $disk;
    }

    public static function disk(): string
    {
        return static::$disk;
    }
}
