# Upgrading

## Unreleased

### Sorted index queries end with the primary key

Rows that tie on every order of an index, global search or action query are now ordered by the
primary key, so pagination no longer repeats or skips a row. The key follows the direction of the
last order: `?sort=name` runs `order by name asc, id asc` and `?sort=-name` runs
`order by name desc, id desc`, which keeps a `(name, id)` index usable. Orders added in `mainQuery()` or `indexQuery()` still rank before
the key. Queries already ordered by the key, and grouped, distinct or union queries, are left
unchanged. Only the order of tied rows changes; a test that pinned it may need updating.

A pivot model gets the key only when it declares one the way Laravel expects: `$incrementing = true`
or `HasUlids`/`HasUuids`. Other pivot tables usually have no `id` column, so they keep their own
orders. When the model can't tell, set it on the repository: `public static ?bool $keyTiebreaker = true;`
for a pivot whose key comes from a custom trait, `false` for any model whose table has no key column.

### Each bulk store and bulk update row reads only its own input

While a bulk row is filled, saved and has its field actions run, the request body holds that
row alone, still keyed by its index: `$request->input("{$row}.title")` and `$request[$row]` work
as before, but `$request->input('0.title')` from row `1`, `$request->all()` and `$request->payload()`
no longer see the other rows. Uploaded files are narrowed the same way. Validation still runs on
the whole payload first, the query string stays readable, and `savedBulk()`, `storedBulk()` and
`updatedBulk()` receive the whole body again. A `$request->merge()` made inside a row is discarded
after that row.

A model event or field callback that read every row's input on each row made a bulk request
quadratic in its size. If one of yours needs the other rows, read them before the bulk loop (in
`allowToBulkStore()` or a validator) or in `savedBulk()`. The narrowing is available as
`$request->forBulkRow($row, $callback)`.

Only the Restify request handed to fields, actions and repository methods is narrowed. The
application request is a separate instance, so a model event or listener that reads `request()`
still sees every row; pass the Restify request along, or read the row from the model, to stay linear.

### Policy deny messages on attach, sync and detach reach the client

`attach*`, `sync*` and `detach*` policy methods that return `Response::deny('message')` (or `denyWithStatus()` / `denyAsNotFound()`) now answer with that message and status instead of the generic "You cannot attach model..." text. A policy returning plain `false` still answers the generic 403.

### Relations a field lazy-loads no longer appear in a `Mergeable` show

A `Mergeable` repository's show endpoint used to run every field's `resolveForShow()`
and then discard the result, but a field that touched a relation as a side effect
(`field('owner', fn () => $this->resource->user->name)`) left that relation loaded on the
model, so it was serialised into `attributes` (as `user`). Show no longer runs the field
pipeline, so such a relation is absent from the response. This now matches the index
endpoint, which never ran it.

If a client reads such a relation from a `Mergeable` show response, request it
explicitly (declare it in `related()` and send `?related=user`).

### `restify:stub` and `DatabaseGenerator` are removed

`php artisan restify:stub` and `Binaryk\LaravelRestify\Generators\DatabaseGenerator` are gone. They
relied on `getDoctrineSchemaManager()` and `getDoctrineColumn()`, which Laravel 11 removed, so the command
could not run on any supported Laravel version.

`doctrine/dbal` was only a dev dependency of Restify, so applications are not affected.

### Attach and sync resolve related ids strictly and store the database key

Attaching or syncing a related model by a key that is not the related model's primary key
(a `getRelatedKeyName()` such as `uuid` or `slug`) now looks the model up with the global
scopes of the related model applied, and an id for an integer key must be an integer.
`"5.0"` and `"5abc"` used to be cast to `5` and attached to that row; they now answer 404,
like any other id that matches no row. Whole numbers sent as strings (`"5"`, `5`) still work.

The pivot row now stores the key as the database holds it, not the client's spelling of it:
a case-insensitive match such as `ABC-1` for a stored `abc-1` writes `abc-1`, and `"05"` for
integer key `5` writes `5`. Check any client that sends decorated ids, and any code that read
the pivot back expecting the exact string it posted.

### Attach and sync answer 404 for related records hidden by a global scope or soft-deleted

Attach and sync looked related ids up by primary key without the related model's global
scopes, so a record hidden by a scope (a tenant or company scope, for example) or
soft-deleted could still be attached or synced. Both now look ids up through the related
model's global scopes, soft deletes included, and answer 404 without writing anything when
any id is not found that way. The `attach{Related}` policy method receives the scoped model.

Detach applies the related model's global scopes too, so it answers 404 for a record a
scope hides. It still finds soft-deleted records, so a pivot row pointing at one can be
removed. A sync that leaves a scoped-out id out of its list still removes that pivot row.
On a relation whose pivot stores a non-primary related key, detach used to answer 404 for a
soft-deleted record; it now finds it and removes the pivot row.

Custom attach and detach handlers are not covered. A field's `attachCallback()` or
`detachCallback()`, an entry in the repository's `$attachers` or `$detachers` map, and an
`attach{Related}()` or `detach{Related}()` method on the repository take over the request
before any related id is looked up. Attach and sync both route through the attach handlers.
They still receive the raw request ids, so scope and authorize those ids yourself.

### Index related lists are limited when `max_per_page` is set

`?related=...` on index now limits has-many-like relations (`HasMany`, `MorphMany`,
`BelongsToMany`) per parent when the request sends `relatablePerPage` or when
`restify.pagination.max_per_page` is set. Apps that already set `max_per_page` get index
related lists capped at each related repository's `$defaultRelatablePerPage` (15 unless
redeclared), clamped by `max_per_page`; they used to be unlimited.

To keep unlimited related lists, unset `max_per_page` or raise `$defaultRelatablePerPage`
on the related repository.

### Index cache: per-repository setters, per-guard keys, `clearCache()` no longer flushes

- `PostRepository::cacheTtl()`, `cacheTags()`, `disableCache()` and `enableCache()` wrote
  statics shared with every repository, so calling one on a repository changed all of them.
  They now only affect the repository they are called on and its children (a setter on a
  base repository applies to every repository extending it; a child's setter or redeclared
  property wins). A property a repository redeclares (`public static int $cacheTtl = 600;`)
  keeps working. Writing a property directly (`PostRepository::$cacheTtl = 60`) still hits
  the shared base property and is still honored for the TTL; use the setters instead. A
  `cacheStore()` setter was added too.
- `clearCache()` no longer tag-flushes, scans keys or flushes the whole store. It replaces a
  per-repository version that is part of the index cache key, so only that repository's
  entries stop being served, on every store, and also while that repository's cache is
  disabled. Old entries expire with their TTL. Entries are still written under the cache tags
  where the store supports them, so `Cache::tags([...])->flush()` keeps working, but
  `clearCache()` no longer uses tags. Keys now end with `:ver_<version>`, and entries written
  by the previous release are not served after upgrading.
- The index cache key now contains the authenticating guard and the user model
  (`guard_web:model_App\Models\User:user_1`), so users of different guards or tables with the
  same id no longer share an entry.
- `restify.repositories.cache.ttl` is now honored. The TTL precedence is: setter, redeclared
  `$cacheTtl`, `$cacheTtl` changed at runtime on the base class, then the config value. A
  configured value other than 300 now takes effect where the hardcoded 300 used to win.

### An invalid `restify.auth.token_ttl` throws instead of never expiring

Login and register treated any `token_ttl` that was not a positive number (`-5`,
`"60m"`, `"abc"`) as "never expires", and so did a tiny value that rounds to 0 seconds
(`0.001`). A typo in `RESTIFY_TOKEN_TTL` silently issued tokens that never expire.

`null`, `false`, `0`, `"0"` and an empty string still mean never expires, as they have
since 10.1.10. Any other value must be a positive
number of minutes that rounds to at least one second, otherwise login and register throw
an `InvalidArgumentException` naming `restify.auth.token_ttl` and the value. Check that
your `RESTIFY_TOKEN_TTL` is unset or a positive number before upgrading. Register now
validates it before creating the user, so a bad value no longer leaves an account behind.

### Unauthorized actions and getters answer 403

Calling an action or getter that exists but whose `canSee` denies the user was meant to
answer 403, but always answered 404: the existence check only searched the ones the user
could already see. It now answers 403. Unknown keys, and show actions or getters called on
index (and vice versa), still answer 404.

Each case now carries its own message:

- 403: `You don't have permission to run this action.` / `You don't have permission to run this getter.`
- 404: `Action not found.` / `Getter not found.`

These replace the shared `Action does not exists or you don't have enough permissions to perform it.`
and its getter twin.

### `setPrefix()` only prefixes the repository it is called on

`PostRepository::setPrefix('api/v1')` also wrote the shared `Repository::$prefix`,
which every repository booted afterwards then took as its own. Users, companies and
the rest moved under `api/v1` too, and their default `api/restify/...` routes started
answering 403 ("Unauthorized to use the route ... Check prefix").

`setPrefix()` now records the prefix for its own repository only, which matches how a
declared `$prefix` already behaved. A `public static $prefix` declared on a repository
still applies to it on boot, and a `setPrefix()` call made before boot still wins over
that declaration. `$prefix` no longer reflects `setPrefix()`; read the effective prefix
with `prefix()`.

If you relied on one `setPrefix()` call moving every repository, call it on each
repository you want prefixed, or declare `public static $prefix` on them (or on a
shared base repository they extend).

### `DeletesFields::deleteFields()` is typed

`deleteFields(RestifyRequest $request, Model $model): void` now declares its parameter and
return types. A controller that uses the `DeletesFields` trait and overrides
`deleteFields()` without them fails with a fatal signature error; add `Model $model` and
`: void` to the override.

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

### Field actions honour `canSee` and `canRun`

A field's `->action(...)` used to run on store, bulk store, update, patch and bulk update
without checking any authorization callback. It now requires the action's `canSee` and
`canRun` to pass; a denial fails the whole request with a `403`. The field's own `canSee`
is not part of this check: it controls visibility, and write-only fields keep working.

- Store and bulk store resolve the action fields and check the action's `canSee` for every
  row before anything is saved, and after the save run exactly those fields.
  `canRun` is checked after the model is saved, so it receives the stored model; the write
  runs in a transaction and is rolled back. Side effects outside the database that already
  ran before a `canRun` denial are not undone: uploads that `File`/`Image` fields already
  stored on disk, fields' `afterStore` callbacks, a `created` observer's mail, a job
  dispatched without `afterCommit`, or an earlier row's field action in a bulk store.
- Update and patch check before the model is filled, so `canRun` receives the model as it
  was before the request and nothing is written. Bulk update checks every row, once, before
  the first row is written, and then runs exactly the field actions it authorized;
  `updateBulk()` called outside `RepositoryUpdateBulkController` authorizes the row itself.
  An up-front authorization from `authorizeUpdateBulkActions()` is used once, and only for
  the model it was made for.
- On every path the fields for the action pass are now resolved before the fill. A
  `fields()` that branches on the resource sees the values from before the request on
  update, patch and bulk update, and the unsaved model on store and bulk store (bulk store
  resolves every row against the repository's initial resource).

Field actions that set none of these callbacks are unaffected. A field action that is not
in the request payload, or whose field is filtered out by `canStore`/`canUpdate`/
`canPatch`/`canUpdateBulk`, is still skipped without a `403`.

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

### `resetPassword` revokes API tokens and remember-me cookies; `forgotPassword` honours the broker throttle

A successful `resetPassword` now rotates the user's `remember_token`, dispatches
`Illuminate\Auth\Events\PasswordReset`, and - when the user model uses Sanctum's
`HasApiTokens` trait or implements its `Laravel\Sanctum\Contracts\HasApiTokens` contract -
deletes every personal access token the user holds, so a stolen remember-me cookie
or API token no longer survives a reset. Open web sessions end only when the app uses
Laravel's `auth.session` middleware, which logs out sessions whose password hash changed. Clients holding a token for that user get a
`401` on their next request and must log in again. Set `restify.auth.revoke_tokens_on_reset`
to `false` (or `RESTIFY_REVOKE_TOKENS_ON_RESET=false`) to keep tokens alive; a published
`config/restify.php` without the key revokes, like the default.

The new password is written to the model's `getAuthPasswordName()` column (the same
column `login` checks) instead of a hardcoded `password`.

The password, `remember_token`, reset-token deletion and token revocation run in one
transaction on the user model's connection (the reset-token table and Sanctum's tokens
are included when they share it); the event fires after it commits. If your `users` table has no
`remember_token` column, set `protected $rememberTokenName = '';` on the user model so
the rotation is skipped - otherwise the save fails and the reset returns a `500`.

`forgotPassword` now skips issuing a new token and sending the email when the broker
created one for that user within `auth.passwords.{broker}.throttle` seconds (60 by
default). The response stays the same generic success, so it reveals nothing about the
account.

The published controller stubs (`php artisan restify:auth`) carry the same changes. A
controller you published earlier keeps its old behaviour until you port them.

Both controllers now read `restify.auth.password_reset_timebox` with a `200000`
microsecond default, so a published `config/restify.php` that predates the key no longer
makes every `forgotPassword` / `resetPassword` request fail with a `500`. Auth controllers
published from the 10.4.33-10.4.51 stubs still read the key without a default: add
`'password_reset_timebox' => (int) env('RESTIFY_PASSWORD_RESET_TIMEBOX', 200_000),` under
`auth` in `config/restify.php`, or re-publish the stubs.

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

### Login, register and verify responses no longer include the model's `$hidden` attributes

These responses serialise the user through its registered repository, and repository
fields do not respect the model's `$hidden`. A `UserRepository` exposing
`field('password')` therefore returned the password hash from `login`, `register` and
`verifyEmail`. The built-in controllers now call `rest($user)->withoutHiddenAttributes()`,
which drops every attribute listed in `$user->getHidden()`, including one a field
exposes under a different `label()`. When the model lists `$visible` instead, every
field whose attribute is not in it is dropped too, as Eloquent's `toArray()` does;
computed fields (`field(fn () => ...)`) are kept. Values a field derives from a hidden
attribute through a closure or callback are not detected - keep those out of your fields.

If you published the auth controllers (`php artisan restify:auth`), add
`->withoutHiddenAttributes()` after `rest($user)` in your `LoginController`,
`RegisterController` and `VerifyController`, or re-publish them.

With `app.debug` on, Restify also logs a warning the first time (per process) a
repository serialises a field whose attribute its model hides - for example
`field('password')` on a `User` that lists `password` in `$hidden`. Nothing is thrown and
the response is unchanged; the warning points at a field that exposes the value on your
regular show and index endpoints. Hide such a field with `hideFromShow()` and
`hideFromIndex()` (it stays writable), or remove it. A `Mergeable` repository is not
checked, since it serialises the model's own `toArray()`.

A test that mocks the `Log` facade strictly (`Log::shouldReceive('error')` without
allowing other calls) fails with `BadMethodCallException` if it serialises such a field
while `app.debug` is on. Fix the field, or allow the call with
`Log::shouldReceive('warning')->zeroOrMoreTimes()`.
