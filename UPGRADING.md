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

### `POST /api/register` stores the email lowercased

The `email` is now lowercased before the `unique` check runs and before the row is
saved, so `John@Example.com` registers as `john@example.com` and a later registration
with any other casing of the same address is rejected as a duplicate. Existing rows
with mixed-case emails are unchanged. Login is unaffected - it still matches the email
exactly (case-sensitive on pgsql/sqlite), so a user who registered before this change
must still log in with the casing their row was stored with.

### Policy cache's fallback ttl is 300 seconds, not 60

`PolicyCache::resolve()` falls back to a 300 second ttl (matching `config/restify.php`'s
own `5 * 60` default) when `restify.cache.policies.ttl` is missing from config entirely,
instead of the previous, inconsistent 60 second fallback. This only affects an app that
enabled `restify.cache.policies.enabled` while publishing a `config/restify.php` that
omits the `ttl` key - a normal config, where the key keeps its default, is unaffected.
