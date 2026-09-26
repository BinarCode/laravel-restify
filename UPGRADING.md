# Upgrading

## Unreleased

### `afterValidation()` now also fires on bulk delete

`DELETE /api/restify/{repository}/bulk/delete` now runs the repository's
`afterValidation()` hook, like every other write endpoint already did. The
validator it receives holds the raw payload as `['keys' => [...]]`.

### `updatedBulk()` and `savedBulk()` receive models

`POST /api/restify/{repository}/bulk/update` used to hand its lifecycle hooks the raw
request body - one array per row, holding only the keys the client sent. They now
receive the updated Eloquent models, which is what the bulk store hooks already passed
and what the documentation already described.

```php
// before - $models held the request payload
public static function updatedBulk(Collection $models, RestifyRequest $request): void
{
    $ids = $models->pluck('id');
}

// after - $models holds Post instances
public static function updatedBulk(Collection $models, RestifyRequest $request): void
{
    $ids = $models->modelKeys();
}
```

A hook that only reads attributes needs no change: a model is `ArrayAccess`, so
`pluck()`, `$row['title']` and `data_get()` keep working for any column. Update a hook
that reads a key the payload carried but the table does not, or that writes back into
the array it was given.

`storedBulk()` is unaffected - it already passed models. `deletedBulk()` is unaffected -
it still passes the deleted rows as attribute arrays, since the models are gone by the
time it runs.

### `relatedRepository`, `viaRelationship` and `repositoryId` no longer read from the body

`attach`, `detach` and `sync` used to resolve the related repository and the relation
through `$request->relatedRepository` / `$request->viaRelationship`, and every write
endpoint resolved the current repository's id through `$request->repositoryId` /
`request('repositoryId')`. All of these are Laravel's magic request accessors, which
read the JSON/form body (or the query string) before falling back to the route
segment - so a request body carrying a same-named key silently redirected which
relation got written to, or which id a hook received, while authorization kept
running against the field and id named by the URL.

Body and query values for `relatedRepository`, `viaRelationship` and `repositoryId`
are now ignored everywhere they used to be read: the relation always comes from the
resolved field's own relation, and the id always comes from the route's
`{repositoryId}` segment.

If you override `attach()`, `detach()`, `sync()`, `show()`, `update()`, `patch()` or
`destroy()` and read one of these off the request yourself, use the hook's
`$repositoryId` argument for the id, and the route for the related repository:

```php
// before
$relatedRepository = $request->relatedRepository;
$repositoryId = $request->repositoryId; // or request('repositoryId')

// after
public function update(RestifyRequest $request, $repositoryId)
{
    // use $repositoryId, which every caller passes in
}

$relatedRepository = $request->relatedRepositoryKey();
```

Prefer `$repositoryId` over `$request->repositoryIdFromRoute()`: the MCP update and
delete tools call `update()` and `destroy()` without a route, so the route accessor
has nothing to read there. `relatedRepositoryKey()` throws `InvalidArgumentException`
when called outside a route that has a `relatedRepository` segment (attach/detach/sync);
the same is true of `repositoryIdFromRoute()` outside a route that has a `repositoryId`
segment.

### Registration validates the password length and `RegisterController::__invoke()` is typed

`POST /api/register` validates `password` with `required|confirmed|min:6` - previously
only `required|confirmed`. A password under 6 characters that used to register now gets
a `422`.

`RegisterController::__invoke()` now declares `: Serializer`. A subclass that overrides
`__invoke()` without also declaring `: Serializer` fatals with `Declaration ... must be
compatible with RegisterController::__invoke(): Serializer`. Add the return type to any
override.

### `canRun()` is enforced over REST, including standalone actions and index-route getters

`canRun(...)` used to run only for MCP action/getter tools; REST silently ignored it. It
is now checked for every action/getter shape - show, standalone, and index (batched or a
single `repositories: 'all'`/id list) - over both REST and MCP.

Standalone actions and index-route getters have no single model to check `canRun`
against, so they now receive `null` there too. A closure typed with a non-nullable model
(`fn (Request $request, Post $post): bool => ...`) TypeErrors when called with `null`.
Type the parameter nullable: `fn (Request $request, ?Post $post): bool => ...`.

### `forgotPassword`'s `url` no longer allows `config('app.url')`'s host

`AllowedResetUrlHost::fromConfig()` used to also allow a client-supplied `url` to target
`config('app.url')`'s host. That entry is removed: `restify.auth.frontend_app_url`
already defaults to `env('APP_URL')`, so it still covers your app URL when
`FRONTEND_APP_URL` is unset; once `FRONTEND_APP_URL` is set to a different host,
`app.url` is your API host and a reset link should never be allowed to target it. If
you relied on `app.url` being allowed while also setting `FRONTEND_APP_URL` to a
different host, add that host to `restify.auth.password_reset_url` or
`restify.auth.frontend_app_url` instead.

If your app published `config/restify.php` before it carried an `auth.frontend_app_url` key, add `'frontend_app_url' => env('FRONTEND_APP_URL', env('APP_URL')),` to it - otherwise `frontend_app_url` resolves to `null` and every client-supplied `url` on `forgotPassword` is rejected.

### Register lowercases the email; login, forgotPassword and resetPassword also match it lowercased

`register` now lowercases the `email` before the `unique` check runs and before the row
is saved, so `John@Example.com` registers as `john@example.com` and a later registration
with any other casing of the same address is rejected as a duplicate. `login`,
`forgotPassword` and `resetPassword` look the user up by the submitted email exactly
first, then by its lowercased form, so a user registered this way can log in and reset
their password with any casing.

No migration is required. Restify does not touch existing rows, and a row stored with a
mixed-case email (`Old@Example.com`) still matches when the user submits that exact
casing. Lowercasing stored emails is an optional cleanup: on a case-sensitive database
(pgsql, sqlite) it also lets those users log in with other casings - check for rows that
collide once lowercased first.

The published controller stubs (`php artisan restify:auth`) use the same
`FindsUserByEmail` lookup and the register stub lowercases the email. A controller you
published before this change keeps its exact-match lookup; add
`use Binaryk\LaravelRestify\Http\Controllers\Concerns\FindsUserByEmail;` and call
`$this->findUserByEmail($userModel, $email)` to match.

### `resetPassword` signs the user out everywhere; `forgotPassword` honours the broker throttle

A successful `resetPassword` now rotates the user's `remember_token`, dispatches
`Illuminate\Auth\Events\PasswordReset`, and - when the user model uses Sanctum's
`HasApiTokens` - deletes every personal access token the user holds, so a stolen session
or API token no longer survives a reset. Clients holding a token for that user get a
`401` on their next request and must log in again. Set `restify.auth.revoke_tokens_on_reset`
to `false` (or `RESTIFY_REVOKE_TOKENS_ON_RESET=false`) to keep tokens alive; a published
`config/restify.php` without the key revokes, like the default.

The password, `remember_token`, reset-token deletion and token revocation run in one
transaction; the event fires after it commits. If your `users` table has no
`remember_token` column, set `protected $rememberTokenName = '';` on the user model so
the rotation is skipped - otherwise the save fails and the reset returns a `500`.

`forgotPassword` now skips issuing a new token and sending the email when the broker
created one for that user within `auth.passwords.{broker}.throttle` seconds (60 by
default). The response stays the same generic success, so it reveals nothing about the
account.

The published controller stubs (`php artisan restify:auth`) carry the same changes. A
controller you published earlier keeps its old behaviour until you port them.

### Policy cache's fallback ttl is 300 seconds, not 60

`PolicyCache::resolve()` falls back to a 300 second ttl (matching `config/restify.php`'s
own `5 * 60` default) when `restify.cache.policies.ttl` is missing from config entirely,
instead of the previous, inconsistent 60 second fallback. This only affects an app that
enabled `restify.cache.policies.enabled` while publishing a `config/restify.php` that
omits the `ttl` key - a normal config, where the key keeps its default, is unaffected.

### `sync` now enforces a field's `canDetach` on the rows it removes

`POST .../sync/{field}` calls the underlying relationship's `sync()`, which both
attaches new rows and detaches rows missing from the payload - but only the attached
side ever ran the field's authorization; a row `sync` removed skipped `canDetach`
entirely, unlike `detach`, which always ran it.

If a `BelongsToMany` field declares `canDetach`, or is a subclass overriding
`authorizedToDetach()`, `sync` now calls it for every currently-attached row the request
would remove, before making any change. A denial gets a `403` and the sync does not run
at all, not even the additions. A `sync` that only adds rows is unaffected. A field
without either behaves exactly as before and runs no extra query.

`sync` also hands `canSync` the canonical related key (`2`, not the `"02"` the client
sent), and a repository overriding `sync()` that passes extra ids to `parent::sync()`
has those ids authorized and written too. `authorizeToSync(RestifyRequest $request)` keeps
its signature and stays the extension point: `sync` always calls it, and an override that
calls `parent::authorizeToSync($request)` gets the same canonical keys, including any ids
a repository `sync()` override added. `authorizeToSyncCanonicalKeys()` is `@internal`,
called only by `Repository::sync()`; override `authorizeToSync()` instead.

If you rely on `sync` being able to remove rows regardless of `canDetach`, either drop
`canDetach` from that field or make its callback return `true` for the ids you expect
`sync` to keep removing.

### Auth routes are throttled by named, per-action rate limiters

`register`, `login`, `verifyEmail`, `forgotPassword` and `resetPassword` used to all
share Laravel's default `throttle:6,1` bucket, keyed by `domain|ip` - so hitting the
limit on one auth route throttled every other auth route from the same IP too, and
every email attempting `login` shared one bucket per IP.

Each route now has its own named limiter, registered via `RateLimiter::for()` in
`RestifyApplicationServiceProvider::boot()`: `restify.register`, `restify.login`,
`restify.verify`, `restify.forgotPassword`, `restify.resetPassword`. `register`,
`verifyEmail`, `forgotPassword` and `resetPassword` are each a 6/minute limit keyed on
the IP alone - `forgotPassword` and `resetPassword` deliberately stay IP-only (not
email-keyed) so a known and an unknown email still share one bucket and get an
identical `429`, preserving the existing account-enumeration protection.

`login` is keyed differently, as two limits enforced together: 6/minute per
`Str::lower($email).'|'.$ip`, plus 30/minute per IP regardless of email. The email+ip
limit means exhausting the limit for one email does not throttle a login attempt
against a different email from the same IP; the IP-wide limit still caps an attacker
spraying many different emails from one IP at 30 attempts/minute.

Restify only registers a limiter under a name your app hasn't already defined
(`RateLimiter::limiter($name) === null`), so an app-defined `RateLimiter::for('restify.login', ...)`
is never overridden, regardless of which provider boots first. If you published the
route stubs (`php artisan restify:auth`), re-publish them or manually change
`throttle:6,1` to the matching `throttle:restify.<action>` on each route - the package
provider registers the limiters regardless of whether the published stubs use them, so
leaving old stubs in place still throttles, just on the old shared 6,1 bucket.

If your `app/Providers/RestifyServiceProvider` overrides `boot()`, call `parent::boot()` (or define the `restify.*` limiters yourself) - otherwise the `restify.*` limiters never get registered and `throttle:restify.*` throws `MissingRateLimiterException` (a `500`).
