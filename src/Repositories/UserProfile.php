<?php

namespace Binaryk\LaravelRestify\Repositories;

use Illuminate\Http\Request;

trait UserProfile
{
    public static $canUseForProfile = false;

    public static $canUseForProfileUpdate = false;

    public static $metaProfile = [];

    /**
     * The attributes PUT and POST /profile write while canUseForProfileUpdate() is false, with the rules that
     * validate them. Nothing else in the payload is written, and an attribute must also be fillable on the model.
     *
     * The key, the password, the remember token and the email are ignored here. The endpoint validates and writes
     * the email and the password itself, and only together with the user's current password.
     *
     * @var array<string, mixed>
     */
    public static $profileUpdateRules = [
        'name' => ['sometimes', 'required', 'string', 'max:255'],
    ];

    public static function canUseForProfile(Request $request): bool
    {
        return is_callable(static::$canUseForProfile)
            ? forward_static_call(static::$canUseForProfile, $request)
            : static::$canUseForProfile;
    }

    public static function canUseForProfileUpdate(Request $request): bool
    {
        return is_callable(static::$canUseForProfileUpdate)
            ? forward_static_call(static::$canUseForProfileUpdate, $request)
            : static::$canUseForProfileUpdate;
    }

    /**
     * @return array<string, mixed>
     */
    public static function profileUpdateRules(Request $request): array
    {
        return static::$profileUpdateRules;
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
