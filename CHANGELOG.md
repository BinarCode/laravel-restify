# Changelog

All notable changes to `laravel-restify` will be documented in this file

## [7.0.0] 2022-07-24
- [x] Adding support for custom ActionLogs (ie `ActionLog::register("project marked active by user Auth::id()", $project->id)`)
- [x] Ensure `$with` loads relationship in `show` requests
- [x] Make sure any action isn't permitted unless the Model Policy exists
- [x] Having a helper method that allow to return data using the repository from a custom controller `PostRepository::withModels(Post::query()->take(5)->get())->include('user')->serializeForShow()` - see `seralizer()`
- [x] Ability to make an endpoint public using a policy method
- [x] Load specific fields for nested relationships (ie: `api/restify/company/include=users.posts[id, name].comments[title]`)
- [x] Load nested for relationships with a nested level higher than 2 (so now you can load any nested level you need `a.b.c.d`)
- [x] Shorter definition of Related fields `HasMany::make('posts')`
- [x] Performance improvements

## [5.0.0] 2021-05-23
- Repositories CRUD + Bulk
- Actions
- Fields
- Search
...
  
## [1.0.0] 2019-12-23

### Added
- RestController - abstract controller to be extended by your API controllers
- RestifyHandler - a global exception handler which transforms many types of generic exceptions into a standard repose with appropriate code for a consistent API 
- AuthService - full support for JWT authentication based on Laravel Passport client token
- Passport installation checker command: `restify:check-passport`
- Passportable - contract used for implementation by the authenticated entity
- RestifySearchable - contract used for search support by the API (should be used along with InteractWithSearch)
- A bunch of generic exceptions which may be used into your project
- Unit and Integration tests
