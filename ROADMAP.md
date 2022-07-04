## Roadmap

- Improve controllers
- Reduce the main Repository class by using traits
- Request validations should be rewritten 
- Revisit the InteractWithRepositories trait and clean model queries accordingly
- Adding support for PHPStan and configure the level 4
- Make sure any action is permitted unless the Model Policy exists
- Add PestPHP support
- Clean up all tests using AssertableJson
- Adding support for custom ActionLogs (ie ActionLog::register("project marked active by user Auth::id()", $project->id))
- Adding a command that lists all Restify registered routes `php artisan restify:routes`
