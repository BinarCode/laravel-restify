## Roadmap

7.x 

### Fixes

- [x] Clean up controllers
- [x] Reduce the main Repository class by using traits
- [ ] Request validations should be rewritten 
- [ ] Revisit the `InteractWithRepositories` trait and clean model queries accordingly
- [x] Clean up all tests using AssertableJson [x]
- [x] Make sure the `include` matches array key firstly, and secondly the relationship name

### Features

- [x] Adding support for custom ActionLogs (ie ActionLog::register("project marked active by user Auth::id()", $project->id))
- [x] Ensure `$with` loads relationship in `show` requests
- [x] Make sure any action isn't permitted unless the Model Policy exists
- [x] Having a helper method that allow to return data using the repository from a custom controller `PostRepository::withModels(Post::query()->take(5)->get())->include('user')->serializeForShow()`
- [ ] Serialize nested relationships

8.x 

### Fixes

- [ ] Adding Larastan support
- [ ] Drop Psalm
- [ ] Adding PestPHP support
- [ ] Adding support for PHPStan and configure the level 4

### Features

- [ ] Adding a command that lists all Restify registered routes `php artisan restify:routes`
- [ ] Ability to make an endpoint public using a policy method
