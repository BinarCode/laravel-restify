# Upgrading

## Unreleased

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
- `fn (Request $request, Post $post): bool => ...` - TypeErrors when called with `null`.
Type the parameter nullable: `fn (Request $request, ?Post $post): bool => ...`.
