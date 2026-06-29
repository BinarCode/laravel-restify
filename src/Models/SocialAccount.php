<?php

namespace Binaryk\LaravelRestify\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Represents a third-party provider account (GitHub, Google, Atlassian, ...)
 * linked to one of the application's users. A single user may link many
 * providers, which is what enables "Connect GitHub" / "Connect Atlassian" style
 * flows on top of the regular authentication.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $provider
 * @property string $provider_id
 * @property string|null $nickname
 * @property string|null $name
 * @property string|null $email
 * @property string|null $avatar
 * @property string|null $access_token
 * @property string|null $refresh_token
 * @property Carbon|null $expires_at
 */
class SocialAccount extends Model
{
    protected $table = 'social_accounts';

    protected $guarded = [];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            config('restify.auth.user_model'),
            'user_id'
        );
    }

    /**
     * Whether the stored provider access token has expired.
     */
    public function tokenExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
