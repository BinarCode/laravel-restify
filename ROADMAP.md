## Roadmap

7.x 

### Fixes & Improvements

- [x] Clean up controllers
- [x] Reduce the main Repository class by using traits
- [x] Revisit the `InteractWithRepositories` trait and clean model queries accordingly
- [x] Clean up all tests using AssertableJson [x]
- [x] Make sure the `include` matches array key firstly, and secondly the relationship name
- [x] Improve performance for queries and relationships

### Features

- [x] Adding support for custom ActionLogs (ie ActionLog::register("project marked active by user Auth::id()", $project->id))
- [x] Ensure `$with` loads relationship in `show` requests
- [x] Make sure any action isn't permitted unless the Model Policy exists
- [x] Having a helper method that allow to return data using the repository from a custom controller `PostRepository::withModels(Post::query()->take(5)->get())->include('user')->serializeForShow()`
- [x] Serialize nested relationships
- [x] Ability to make an endpoint public using a policy method
- [x] Load specific fields for nested relationships
- [x] Load nested for relationships with a nested level higher than 2
- [x] Shorter definition of Related fields

8.x 

### Fixes

- [ ] Adding Larastan support
- [ ] Drop Psalm
- [ ] Adding PestPHP support
- [ ] Adding support for PHPStan and configure the level 4
- [ ] Request validations should be rewritten

### Features

- [x] Adding a command that lists all Restify registered routes `php artisan restify:routes`
- [ ] UI for Restify
- [x] Support for Laravel 10
- [x] Custom namespace and base directory for repositories
- [ ] Deprecate `show`  and use `view` as default policy method for `show` requests
- [ ] Deprecate `store`  and use `create` as default policy method for `store` requests so it's Laravel compatible
