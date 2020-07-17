<?php

namespace Binaryk\LaravelRestify\Repositories;

use Illuminate\Http\Request;

trait UserProfile
{
    public static $canUseForProfile = false;

    public static $metaProfile = [];

    public static function canUseForProfile(Request $request)
    {
        return is_callable(static::$canUseForProfile)
            ? forward_static_call(static::$canUseForProfile, $request)
            : static::$canUseForProfile;
    }

    public static function metaProfile(Request $request): array
    {
        return static::$metaProfile;
    }

    public function resolveShowMeta($request)
    {
        return [
            'authorizedToShow' => $this->authorizedToShow($request),
            'authorizedToStore' => $this->authorizedToStore($request),
            'authorizedToUpdate' => $this->authorizedToUpdate($request),
            'authorizedToDelete' => $this->authorizedToDelete($request),
        ] + static::metaProfile($request);
    }
}
