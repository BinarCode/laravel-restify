## Roadmap

7.x 

### Fixes

- [ ] Clean up controllers
- [ ] Reduce the main Repository class by using traits
- [ ] Request validations should be rewritten 
- [ ] Revisit the `InteractWithRepositories` trait and clean model queries accordingly
- [ ] Adding support for PHPStan and configure the level 4
- [ ] Clean up all tests using AssertableJson [x]
- [x] Make sure the `include` matches array key firstly, and secondly the relationship name

### Features

- [ ] Adding support for custom ActionLogs (ie ActionLog::register("project marked active by user Auth::id()", $project->id))
- [ ] Ensure `$with` loads relationship in `show` requests
- [ ] Make sure any action isn't permitted unless the Model Policy exists
- [ ] Ability to make an endpoint public using a policy method

8.x 

### Fixes

- [ ] Adding Larastan support
- [ ] Drop Psalm
- [ ] Adding PestPHP support

### Features

- [ ] Serialize nested relationships
- [ ] Having a helper method that allow to return data using the repository from a custom controller `PostRepository::withModels(Post::query()->take(5)->get())->include('user')->serializeForShow()`
- [ ] Adding a command that lists all Restify registered routes `php artisan restify:routes`
