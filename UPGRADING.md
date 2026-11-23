# Upgrading

## Unreleased

### File fields store only `http` and `https` URLs, and never pass a URL to the disk

URL input on a `File`, `Image` or `Base64File` field used to store any string PHP accepts as a URL. It now
stores only an absolute `http` or `https` URL with a host and without a `..` segment. Any other string is
ignored, as a string that is not a URL always was: the request succeeds and the column keeps its value. A
client that sent another scheme (`ftp://`, `s3://`, ...) has to send an `http` or `https` URL, or upload the
file.

The field also no longer hands a stored value to its disk unless that value is a path on the disk, meaning it
is not a URL and has no `..` segment. For any other value:

- Field delete (`DELETE /{repository}/{id}/field/{field}`), a prunable replacement and pruning on record
  delete clear the column and delete no file. They used to ask the disk to delete the value, which could
  resolve to another file on that disk.
- `Image` resolves to its default value without asking the disk whether the value exists.
- `resolveUsingFullUrl()` and `resolveUsingTemporaryUrl()` return an `http` or `https` URL as stored, and
  `null` for anything else, instead of a disk URL built from the value.

This also covers rows written before the upgrade, so no data migration is needed. A callback you passed to
`delete()` still receives the stored value unchanged; if it passes that value to a disk, guard it with
`File::isStoragePath($path)`.

### Laravel 11 is no longer supported

Restify now requires Laravel 12 or 13. Laravel 11 is past its security support, and Composer 2.11
refuses to install it by default. An app still on Laravel 11 keeps resolving the last 10.4.x
release; upgrade Laravel to get later fixes.

### The MCP store tool downloads a remote file only from the addresses it validated

When the MCP store tool is given a URL for a file field, it used to check the addresses of the host and
then let the download resolve the host a second time. The download now connects only to an address that
passed the check. The host of the URL is still sent in the `Host` header, and the TLS certificate is still
verified against it. Some URLs that were fetched before are now refused:

- A host is refused when any of its `A` or `AAAA` addresses is not a public unicast address. Next to
  private, loopback and link-local addresses, this now covers carrier-grade NAT (`100.64.0.0/10`),
  `192.0.0.0/24`, the documentation and benchmarking blocks, multicast, and IPv6 addresses that carry an
  IPv4 address: IPv4-mapped, 6to4, Teredo, and NAT64 of an address that is not public. `AAAA` records
  were not checked before.
- The host has to be a fully qualified ASCII host name or an IP address in its standard notation. Decimal,
  hexadecimal, octal and shortened spellings of an IPv4 address, a single-label name, and a name with a
  trailing dot or an underscore are refused. So is a URL with whitespace or a control character in it.
  Send an internationalized name in its `xn--` form and encode spaces as `%20`.
- Host names are resolved with DNS `A` and `AAAA` queries. A name that exists only in the server's hosts
  file no longer resolves, and a lookup that fails for either record type refuses the URL.
- Only a `2xx` response becomes the file. A redirect was never followed, but its body used to be stored;
  it no longer is. A response cut short by the timeout is discarded too.

`isSafePublicUrl()` and `fetchRemoteFile()` keep their signatures, and the store tool still calls both. To
resolve hosts or download files your own way, bind `Binaryk\LaravelRestify\MCP\Files\HostResolver` or
`Binaryk\LaravelRestify\MCP\Files\RemoteFileTransport` in a service provider.

### Global search applies the `show` policy and `indexQuery()`

`GET /search` and the MCP `global-search` tool now leave out every matched model the user is not
allowed to `show`, the same way the index does. A policy without a `show` method therefore drops all
of that repository's search results; add `show` or set `$globallySearchable = false`.

The MCP `global-search` tool now also runs the repository `indexQuery()`, as `GET /search` already
did, so rows your `indexQuery()` hides no longer appear in MCP search results.

### The MCP profile tool reads only `include`

The `users-profile-tool` (and `execute-operation` with `operation_type: "profile"`) now constrains the
authenticated user's key on the query itself and forwards only `include` / `related` to the repository.
Every other argument, such as a match filter, `search`, `sort`, `page` or `perPage`, used to reach the
underlying index query and is now ignored, whether it is sent as a tool argument or on the URL of the MCP
endpoint. The tool no longer needs `id` in the repository's `$match`.

A call that sends only `include` gets the same response as before: the index envelope with the
authenticated user as its only row, or an empty `data` when `mainQuery()`, `indexQuery()` or the `show`
policy hide that user. The response is no longer read from or written to the index cache.

### Updates and deletes are scoped by `mainQuery()`

Update (`PUT`), patch (`PATCH`), delete, bulk update, bulk delete, field delete
(`DELETE /{repository}/{id}/field/{field}`) and the MCP update and delete tools now load their models
through the repository `mainQuery()`, the same way show and index do. A record your `mainQuery()` hides
now answers 404, and a bulk request that includes one changes nothing, instead of the record being changed whenever the policy allowed it.

If your `mainQuery()` hides records that users still edit, such as archived ones they restore through a
`PATCH`, either move that filter to `indexQuery()` or set `restify.repositories.apply_main_query_to_writes`
to `false`.

### Attach, detach and sync are scoped by `mainQuery()`

Attach (`POST /{repository}/{id}/attach/{relation}`), detach and sync now load the parent model through
the repository `mainQuery()`, like updates and deletes. A parent your `mainQuery()` hides now answers 404
instead of having its pivot rows changed whenever the policy allowed it. The same
`restify.repositories.apply_main_query_to_writes` key turns this off.

The `detach{Relation}` policy method now receives the parent model. It used to receive an empty
instance of the model, so a check on the parent's attributes there denied every detach. If your policy
worked around that, the workaround can go.

### The POST advanced filters endpoint is an index request

`POST /{repository}/apply-restify-advanced-filters` lists the repository like `GET /{repository}`, but it
was not treated as an index request: the repository `indexQuery()` was skipped, `fields()` was used even
when the repository defines `fieldsForIndex()`, and relationships hidden from the index were still
returned. It now behaves as the index does, with the filters read from the body:

- Rows your `indexQuery()` excludes are no longer returned or counted in `meta`, and the ordering and
  eager loads your `indexQuery()` adds apply here too.
- A repository that defines `fieldsForIndex()` serializes those fields on this endpoint.
- A relationship declared with `hideFromIndex()` is left out.
- `$request->isIndexRequest()` is true and the request is a `RepositoryIndexRequest`, so code of yours
  that branches on either (a `collectMiddlewares()` override, a field callback) takes the index branch.

If a client relied on this endpoint to read rows or fields the index does not return, expose them on
purpose instead, for example through a getter or a dedicated repository.

### Nested routes authorize the parent

The nested routes (`GET`, `POST`, `PUT` and `DELETE` on `/{parent}/{parentId}/{relation}[/{id}]`) now load the
parent by its route key (`getRouteKeyName()`, as every other lookup does) through its repository `mainQuery()`, and
authorize it before the child is touched:

- A parent your `mainQuery()` hides, or one that does not exist, answers 404. A missing parent used to fail
  with a server error, and a hidden one listed, showed and accepted children.
- Every nested route needs the parent policy `show` (403 otherwise).
- Creating, updating or deleting a child through the parent also needs the parent policy `update`, since it
  changes what the parent holds. Reading children needs `show` only.

These checks run before the child is validated or authorized, so its own policy and rules are unchanged. They also
run on a cached index, and the index cache key now includes the parent, so `/users/1/posts` and `/users/2/posts` no
longer share a cached list. The `restify.repositories.apply_main_query_to_writes` key turns off the `mainQuery()` part
for nested writes only; nested reads always apply it, as show does, and the policy checks always run.

Creating a child through its parent (`POST /{parent}/{parentId}/{relation}`) also changed:

- For a `HasMany`, `HasOne`, `MorphMany` or `MorphOne` relation, the parent key (and the morph type) is put into the
  request before the child is validated, so the child's rules and policy see it. It used to be set only when the
  child was saved, after validation. A payload that names a different parent for that key now answers 422 instead
  of being overwritten silently. A null value counts as not sent, an integer key matches however it is spelled
  (`"05"` for `5`), and the morph type may be sent as the morph alias or as the class it maps to; the alias is stored.
- For a `HasOne` or `MorphOne` relation, a parent that already has that child answers 409 instead of getting a
  second row.
- A `BelongsToMany` or `MorphToMany` child can no longer be created this way (400). It used to create the related
  model and attach it to the parent with no attach policy or pivot fields; use the attach endpoint instead. Any other
  relation type failed with a server error and now answers 400 too.

### Nested `related` paths have to be declared

`?related=` and `?include=` used to load any nested path below a declared relation, as long as the models had those
relations. On index, nested index and global search requests, in the queries of index actions and getters, and in the
MCP index tool, every segment now has to be declared:

- Below an eager field (`HasMany::make('posts', PostRepository::class)`) the next segment has to be in the `related()`
  of that field's repository, as the relations docs describe. Such paths load as before.
- Below a relation declared by its name (`'posts'`), or an eager field declared under a dotted key
  (`'posts.comments' => HasMany::make('posts')`), nothing more is loaded. These are serialized by the model, not by a
  repository, so a request could read any relation of the related models, to any depth, without their repositories'
  fields and policies. With `$related = ['posts']`, `?related=posts.comments` now returns the posts without comments.

A path that is not declared is skipped, the same way an unknown relation always was: the response is a 200 without it.
If a client relies on such a path, declare it. Either add the dotted path itself (`'posts.comments'`) to `related()`,
or use an eager field and declare the next relation in its repository.

Restify also no longer calls a requested segment on the model to find out whether it is a relation. A declared
relation is eager loaded only when the model defines it as a method or registers it with `resolveRelationUsing()`.
One that exists only through a model's own `__call` is still returned, loaded for each row instead.

### The repository of a request comes from its route, never from its input

A prefixed repository (`$prefix`) and a public one (`$public`) get routes of their own, which spell the repository
out in the URI instead of matching a `{repository}` segment. On those routes, and on the custom routes a repository
registers through `routes()`, the repository used to be read from the request: from an `mcp_repository_key` input,
and otherwise by searching the whole request URI, query string included, for a repository path. Request input could
therefore make such a route answer with another repository.

A prefixed or public route now always runs against the repository it was registered for, and a custom repository
route is matched on its path alone. An `mcp_repository_key` input is ignored everywhere. Routes with a `{repository}`
segment always read that segment and are unchanged.

The MCP getter, action and update tools sent that input to say which repository a call targets. Every MCP operation
tool (index, show, store, update, delete, profile, actions and getters) now binds its repository on the request it
builds, so `$request->repository()` answers the tool's own repository in all of them. Inside the index, show, store,
delete and profile tools it used to fail, as those tools passed no repository at all.

If you build a Restify request yourself outside a repository route (a custom MCP tool, a command, a job) and merged
`mcp_repository_key` into it, bind the repository instead:

```php
// before
$request->merge(['mcp_repository_key' => PostRepository::uriKey()]);

// after
$request->forRepository(PostRepository::uriKey());
```

### Prefixed and public repository routes have route names of their own

The routes a prefixed repository (`$prefix`) or a public one (`$public`) gets for itself used to share the names of
the default `{repository}` routes: `GET /api/v1/posts` was named `restify.api.index`, like
`GET /api/restify/{repository}`. Laravel cannot cache routes that share a name, so `php artisan route:cache` and
`php artisan optimize` failed in an application with such a repository:

```
Unable to prepare route [api/v1/posts/filters] for serialization. Another route has already been assigned name [restify.api.filters.index].
```

Those routes are now named after their repository, and the routes can be cached:

| Route                                           | Before              | Now                                |
|:------------------------------------------------|:--------------------|:-----------------------------------|
| `GET /api/v1/posts`, from `$prefix = 'api/v1'`  | `restify.api.index` | `restify.api.prefixed.posts.index` |
| `GET /api/restify/posts`, from `$public = true` | `restify.api.index` | `restify.api.public.posts.index`   |

The pattern is `restify.api.prefixed.{uriKey}.{name}` and `restify.api.public.{uriKey}.{name}`, where `{name}` is
what follows `restify.api.` in the name of the default route (`index`, `show`, `store`, `actions.index`, ...). A
repository that is both prefixed and public gets both sets. The default `{repository}` routes keep their names, and
no URI or middleware changes. The advanced repositories docs list every name under "Route names".

What to check in your application:

- A request to a prefixed or public repository now reports the new name. Code that compares
  `$request->routeIs(...)`, `Route::currentRouteName()` or `$request->route()->getName()` with an exact name such as
  `restify.api.index`, for example in a middleware, a rate limiter or a log, no longer matches those requests. Add
  the new name, or match by pattern: `restify.api.*` still covers every Restify route, `restify.api.public.*` every
  route of a public repository.
- Where `route()` resolves a Restify route name, such as in a console command or a queued job,
  `route('restify.api.index', ['repository' => 'users'])` and the other default names now always build the default
  URL. While a prefixed or public repository shared the name, the framework resolved it to one of the routes, which
  could be the prefixed or public one, and built `/api/v1/posts?repository=users`.

### Show actions and show getters are scoped by `mainQuery()`

A show action (`POST /{repository}/{id}/actions`) or show getter (`GET /{repository}/{id}/getters`) now loads its model through the repository `mainQuery()` before the action or getter `indexQuery()`, the same way show, index and bulk actions already did. This also covers the MCP action and getter tools. A record your `mainQuery()` hides now answers 404 instead of running the action or getter on it.

To keep the previous behaviour, set `restify.repositories.apply_main_query_to_actions_and_getters` to `false`.

### Actions and getters are scoped by `indexQuery()` and `showQuery()`

Actions and getters reached their rows through the repository `mainQuery()` only, so they could run on, or
read, rows the index does not list and records the show route does not find. They now use the same scopes:

- An index action (`POST /{repository}/actions`) receives only rows the repository `indexQuery()` lists, for
  `repositories: "all"` and for a list of keys alike, before the action's own `indexQuery()` is applied. A key
  outside that set is skipped, as a key hidden by `mainQuery()` already was, so the action can receive fewer
  models than the keys sent.
- `$request->filteredQuery()`, which index getters read their rows from, and
  `GetterRequest::collectRepositories()` are limited by the repository `indexQuery()` in the same way.
- A show action (`POST /{repository}/{id}/actions`) or show getter (`GET /{repository}/{id}/getters/{getter}`)
  loads its model through the repository `showQuery()` after `mainQuery()`. A record your `showQuery()` excludes
  answers 404 instead of running the action or getter on it.

This also covers the MCP action and getter tools. Standalone actions load no models and are unchanged. The
ordering and eager loads your `indexQuery()` or `showQuery()` adds now apply to these queries too.

If your `indexQuery()` hides rows for presentation only (say it lists published posts) and an index action or
getter must still reach the hidden ones, set `restify.repositories.apply_index_query_to_actions_and_getters` to
`false`. `restify.repositories.apply_show_query_to_actions_and_getters` does the same for `showQuery()`.

Index actions and index getters still do not check the model policy on the rows they reach. Restrict rows in
the repository queries, and use `canRun()` for a check on each model.

### Show actions and show getters need the `show` policy

A show action (`POST /{repository}/{id}/actions`) or show getter (`GET /{repository}/{id}/getters/{getter}`)
now answers 403 unless the user is allowed to `show` the model it runs on, the check the show route makes. It
runs after the model is found, so a record outside `mainQuery()` or `showQuery()` still answers 404, and before
`canRun()`. The MCP action and getter tools apply it too and report the denial as an error.

A policy without a `show` method denies, as it does on the show route. If a show action or show getter must
stay available to users who may not show the record, allow `show` for them, or make it a standalone action
that authorizes itself. No other policy method is checked, and there is no config key for this check.

### Sorted index queries end with the primary key

Rows that tie on every order of an index, global search or action query are now ordered by the
primary key, so pagination no longer repeats or skips a row. The key follows the direction of the
last order: `?sort=name` runs `order by name asc, id asc` and `?sort=-name` runs
`order by name desc, id desc`, which keeps a `(name, id)` index usable. Orders added in `mainQuery()` or `indexQuery()` still rank before
the key. Queries already ordered by the key, and grouped, distinct or union queries, are left
unchanged. Only the order of tied rows changes; a test that pinned it may need updating.

A pivot model gets the key only when it declares one the way Laravel expects: `$incrementing = true`
or `HasUlids`/`HasUuids`. Other pivot tables usually have no `id` column, so they keep their own
orders. A pivot whose key comes from elsewhere (a custom trait, a plain `id` column) implements
`Binaryk\LaravelRestify\Contracts\KeyedPivot` to keep the tiebreaker.

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

### Bulk store honours a field's `canStore`

Bulk store (`POST /{repository}/bulk`) filtered its fields with `canUpdateBulk` instead of `canStore`.
A field guarded with `canStore()` was therefore skipped by a single store but written by a bulk
store. Bulk store now checks `canStore` as well: a field it denies is skipped for that row, exactly
as on a single store. The value sent for it is ignored, its default value and its field action are
not applied, and the request still succeeds.

The closure runs for each row and receives the bulk request (`RepositoryStoreBulkRequest`) narrowed
to that row, so `$request->payload()` holds that row alone, keyed by its index, and
`$request->input('title')` is empty. A `canStore` closure that type-hints `RepositoryStoreRequest`
fails with a `TypeError` on a bulk store; type-hint `RestifyRequest` or `Illuminate\Http\Request`.

Bulk store still skips a field whose `canUpdateBulk` returns `false`, as it did before. Bulk update
is unchanged: it checks `canUpdateBulk` and not `canUpdate`.

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

### Loaded relations are no longer merged into `Mergeable` attributes

A `Mergeable` repository merges the model's `toArray()` into `attributes`, and `toArray()` also
serialises every relation loaded on the model. A related model therefore appeared a second time,
as a raw array under `attributes`, next to its entry under `relationships`, and that copy skipped
the related repository: its fields, their `canSee` and its `show` policy. `attributes` now leave
out every loaded relation, however it was loaded: `?related=` / `?include=` on the index, the
repository `$with`, a `lazy()` field, the model `$with`, an accessor that reads the relation, a
`BelongsTo` field that associates it on store or update, or the `pivot` of a many-to-many related
model. Show, index, store, update and the MCP tools behave the same.

Columns, casts, `$hidden` / `$visible`, `$appends` and a model's own `toArray()` are merged as
before. If a model attribute and a loaded relation share a name, the attribute is returned.

If a client reads `attributes.user` (or `attributes.comments`, `attributes.pivot`, ...) from a
`Mergeable` repository, read `relationships.user` instead: declare the relation in `related()`
with its repository and request it with `?related=user`. Pivot columns belong in `withPivot()`
fields, which are returned under `pivots`. To keep a value from a related model inside
`attributes`, expose that value through an appended accessor on the model.

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

### Page size, bulk rows and `related` depth are capped by default

One request could ask for any page size, any number of bulk rows and any `related` nesting depth. Each
of these now has a limit that applies without any configuration. The defaults are applied in code, so an
application whose published `config/restify.php` predates the new keys is capped as well.

| What | Config key (environment variable) | Default | A request above the limit |
| --- | --- | --- | --- |
| `perPage`, `page[size]`, `relatablePerPage` | `restify.pagination.max_per_page` (`RESTIFY_MAX_PER_PAGE`) | 100 | is clamped down to the limit |
| Rows in a bulk store, bulk update or bulk delete | `restify.bulk.max_rows` (`RESTIFY_MAX_BULK_ROWS`) | 1000 | answers `422`, nothing is written |
| Levels in a `related` / `include` path | `restify.related.max_depth` (`RESTIFY_MAX_RELATED_DEPTH`) | 5 | has its deeper levels ignored |

The same limits apply to the MCP tools, which read `perPage` and `include` the way the REST endpoints do.

What changes if you never set `max_per_page`:

- `?perPage=500` now returns 100 rows per page, and `meta.per_page` and `meta.last_page` say so. A client
  that fetched everything with one large `perPage` has to follow the pages, or you raise the cap.
- `?relatablePerPage=500` now returns at most 100 related rows per parent.
- A repository whose `$defaultPerPage` or `$defaultRelatablePerPage` is above 100 keeps that default, and
  clients may request up to it on that repository. Responses to requests that send no page size are unchanged.
- Index related lists are still not limited unless the request sends `relatablePerPage`: the default cap
  alone does not opt in to the behaviour described in the next section.

`null` no longer means "no cap" for `max_per_page`: `null`, an empty string and any other non-numeric value now
mean the default of 100. Use `0`, a negative number or `false` to remove a limit; this works for all three keys. A
`max_per_page` you already set to a number behaves exactly as before.

A bulk request with more than 1000 rows answers `422` with a `bulk` validation error before any row is
authorized, validated or written. Split the rows over several requests or raise `restify.bulk.max_rows`.

A `related` path longer than five levels is loaded up to the fifth, so `a.b.c.d.e.f` returns `a.b.c.d.e`. A
dotted path the repository declares itself in `$related` is still loaded in full when requested on that
repository. Raise `restify.related.max_depth` if your clients nest deeper.

To get the previous, unlimited behaviour back:

```dotenv
RESTIFY_MAX_PER_PAGE=0
RESTIFY_MAX_BULK_ROWS=0
RESTIFY_MAX_RELATED_DEPTH=0
```

If your published config has no `bulk` and `related` sections the environment variables still work. To set the
values in the file instead, add:

```php
'bulk' => [
    'max_rows' => env('RESTIFY_MAX_BULK_ROWS', 1000),
],

'related' => [
    'max_depth' => env('RESTIFY_MAX_RELATED_DEPTH', 5),
],
```

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

### The profile update without the repository writes listed attributes only, and credentials need the current password

`PUT` and `POST /profile` update the user model directly when the users repository uses the
`UserProfile` trait and `canUseForProfileUpdate()` is `false`, which is the trait's default. That
direct update used to write every `$fillable` attribute found in the payload, and changed the
password with only `password` and `password_confirmation`. It now writes validated input only. The
repository-driven update (`canUseForProfileUpdate()` returning `true`, or a repository that does not
define that method) is unchanged.

**Attributes.** Only the attributes the repository lists in `profileUpdateRules()` are written, each
validated by its rules. The default is `name` (`sometimes|required|string|max:255`). Every other
column in the payload is now ignored, as a non-fillable one already was: flags and role or tenant
columns, `email_verified_at`, `avatar`, the primary key, and any other profile column your
`$fillable` lists. To keep editing a column through the profile, list it:

```php
use Binaryk\LaravelRestify\Repositories\UserProfile;
use Illuminate\Http\Request;

class UserRepository extends Repository
{
    use UserProfile;

    public static function profileUpdateRules(Request $request): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
        ];
    }
}
```

A listed attribute must still be fillable on the model. The primary key, the password column, the
remember token and `email` are ignored in that list. A repository that defines
`canUseForProfileUpdate()` without the trait gets the `name` default unless it also defines
`profileUpdateRules()`.

**Password.** A password change now needs `current_password` next to `password` and
`password_confirmation`, and answers `422` without it. The new password must be a string and is
validated with `Password::defaults()`: 8 characters unless your application defines its own
defaults, where it used to be `min:5`. It is hashed and written to the model's
`getAuthPasswordName()` column, whether or not that column is fillable.

**Email.** An email change now needs `current_password` too, because whoever controls the address
can reset the password. The address must be a valid email, at most 255 characters, unique in the
user model's own table (the check used to be hard-coded to `users`), and it is stored lowercased, as
`register` stores it. Sending the current address back, in any casing, is not a change: it needs no
password and is not rewritten. When the model implements `MustVerifyEmail`, a change sets
`email_verified_at` to `null` and sends the `VerifyEmail` notification `register` sends; the
notification is only sent when the `restify.verify` route is registered or
`VerifyEmail::createUrlUsing()` is set.

Clients that change the email or the password through this endpoint have to send
`current_password`. A user without a password, such as one created through a social login, cannot
pass that check. If these rules do not fit, switch to the repository-driven update, where your own
fields, rules and `update` policy decide.

A request whose authenticated user is not an Eloquent model now answers `401` here instead of failing.

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
