<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;

class GenerateRepositoriesCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'restify:generate:repositories
                            {--force : Overwrite existing repositories}
                            {--skip-preview : Skip preview and generate immediately}
                            {--structure= : Repository structure (flat|domains)}
                            {--only= : Only generate repositories for specific models (comma-separated)}
                            {--except= : Exclude specific models (comma-separated)}';

    protected $description = 'Generate repositories for all models in the application';

    protected Filesystem $files;

    protected string $chosenStructure;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $this->info('🚀 Analyzing models and generating repositories...');

        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        // Discover all models in the application
        $models = $this->discoverModels();

        if ($models->isEmpty()) {
            $this->warn('No models found in the application.');

            return self::FAILURE;
        }

        // Filter models based on options
        $models = $this->filterModels($models);

        if ($models->isEmpty()) {
            $this->warn('No models to generate repositories for after filtering.');

            return self::FAILURE;
        }

        // Choose repository structure
        $this->chooseRepositoryStructure();

        // Show preview unless skipped
        if (! $this->option('skip-preview')) {
            $this->showPreview($models);

            if (! $this->confirm('Do you want to proceed with generating these repositories?')) {
                $this->comment('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        $this->info('Generating repositories...');

        // Generate repositories for each model
        $generatedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($models as $modelData) {
            try {
                $result = $this->generateRepositoryForModel($modelData);

                if ($result === 'generated') {
                    $generatedCount++;
                    $this->line("✅ Generated repository for {$modelData['name']}");
                } elseif ($result === 'skipped') {
                    $skippedCount++;
                    $this->line("⏭️  Skipped {$modelData['name']} (repository exists)");
                } else {
                    $errorCount++;
                    $this->line("❌ Failed to generate repository for {$modelData['name']}");
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("❌ Error generating repository for {$modelData['name']}: ".$e->getMessage());
            }
        }

        $this->newLine();
        $this->comment('🎉 Repository generation complete!');
        $this->info("Generated: {$generatedCount} repositories");
        if ($skippedCount > 0) {
            $this->info("Skipped: {$skippedCount} repositories (already exist)");
        }
        if ($errorCount > 0) {
            $this->warn("Errors: {$errorCount} repositories failed");
        }

        $this->newLine();
        $this->comment('Next steps:');
        $this->comment('  1. Review the generated repositories');
        $this->comment('  2. Add custom validation rules and relationships');
        $this->comment('  3. Configure authorization policies');
        $this->comment('  4. Test your API endpoints');

        return self::SUCCESS;
    }

    protected function discoverModels(): Collection
    {
        $models = collect();

        try {
            $finder = new Finder;
            $finder->files()
                ->in(app_path())
                ->name('*.php')
                ->notPath('Http')
                ->notPath('Console')
                ->notPath('Exceptions')
                ->notPath('Providers')
                ->notPath('Restify');

            foreach ($finder as $file) {
                $relativePath = str_replace(app_path().DIRECTORY_SEPARATOR, '', $file->getRealPath());
                $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
                $className = 'App\\'.str_replace(['/', '.php'], ['\\', ''], $relativePath);

                // Check if it's a valid model class
                if (class_exists($className) && $this->isModel($className)) {
                    $models->push([
                        'class' => $className,
                        'name' => class_basename($className),
                        'file_path' => $file->getRealPath(),
                        'namespace' => $this->getModelNamespace($className),
                        'table' => $this->getModelTable($className),
                        'fields' => $this->analyzeModelFields($className),
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->error('Error discovering models: '.$e->getMessage());
        }

        return $models->sortBy('name');
    }

    protected function isModel(string $className): bool
    {
        try {
            $reflection = new \ReflectionClass($className);

            // Check if it's an instantiable class
            if (! $reflection->isInstantiable()) {
                return false;
            }

            // Check if it extends Eloquent Model
            if (! $reflection->isSubclassOf('Illuminate\\Database\\Eloquent\\Model')) {
                return false;
            }

            // Skip Laravel's built-in models and common base classes
            $skipClasses = [
                'Illuminate\\Database\\Eloquent\\Model',
                'Laravel\\Sanctum\\PersonalAccessToken',
                'Spatie\\Permission\\Models\\Role',
                'Spatie\\Permission\\Models\\Permission',
            ];

            if (in_array($className, $skipClasses)) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function getModelNamespace(string $className): string
    {
        $parts = explode('\\', $className);
        array_pop(); // Remove class name

        return implode('\\', $parts);
    }

    protected function getModelTable(string $className): ?string
    {
        try {
            if (class_exists($className)) {
                $model = new $className;

                return $model->getTable();
            }
        } catch (\Exception $e) {
            // If we can't instantiate the model, guess the table name
            $modelName = class_basename($className);

            return Str::snake(Str::plural($modelName));
        }

        return null;
    }

    protected function analyzeModelFields(string $className): array
    {
        $fields = [];

        try {
            if (! class_exists($className)) {
                return $fields;
            }

            $model = new $className;
            $tableName = $model->getTable();

            if (! Schema::hasTable($tableName)) {
                return $fields;
            }

            $columns = Schema::getColumnListing($tableName);

            foreach ($columns as $column) {
                $columnType = Schema::getColumnType($tableName, $column);

                $fields[] = [
                    'name' => $column,
                    'type' => $columnType,
                    'restify_field' => $this->mapColumnToRestifyField($column, $columnType),
                ];
            }
        } catch (\Exception $e) {
            // If we can't analyze the model, that's okay
        }

        return $fields;
    }

    protected function mapColumnToRestifyField(string $column, string $columnType): string
    {
        // Skip ID field as it's handled automatically
        if ($column === 'id') {
            return 'id()';
        }

        // Skip foreign key columns - they will be handled as relationships
        if (Str::endsWith($column, '_id') && $column !== 'id') {
            return null; // Will be filtered out
        }

        // Start building the field
        $field = "field('{$column}')";

        // Add type-specific modifiers
        switch ($columnType) {
            case 'string':
            case 'varchar':
                // Check for special string types
                if (Str::contains($column, 'email')) {
                    $field .= '->email()';
                } elseif (Str::contains($column, 'password')) {
                    $field .= '->password()->storable()';
                }
                break;

            case 'text':
            case 'longtext':
            case 'mediumtext':
                $field .= '->textarea()';
                break;

            case 'integer':
            case 'bigint':
            case 'smallint':
                $field .= '->number()';
                break;

            case 'boolean':
            case 'tinyint':
                $field .= '->boolean()';
                break;

            case 'date':
                $field .= '->date()';
                break;

            case 'datetime':
            case 'timestamp':
                $field .= '->datetime()';
                break;

            case 'decimal':
            case 'float':
            case 'double':
                $field .= '->number()';
                break;

            case 'json':
                $field .= '->json()';
                break;
        }

        // Handle timestamps and other readonly fields
        if (in_array($column, ['created_at', 'updated_at', 'deleted_at', 'email_verified_at'])) {
            $field .= '->readonly()';
        }

        return $field;
    }

    protected function filterModels(Collection $models): Collection
    {
        // Filter by --only option
        if ($only = $this->option('only')) {
            $onlyModels = array_map('trim', explode(',', $only));
            $models = $models->filter(function ($model) use ($onlyModels) {
                return in_array($model['name'], $onlyModels, true);
            });
        }

        // Filter by --except option
        if ($except = $this->option('except')) {
            $exceptModels = array_map('trim', explode(',', $except));
            $models = $models->reject(function ($model) use ($exceptModels) {
                return in_array($model['name'], $exceptModels, true);
            });
        }

        return $models;
    }

    protected function chooseRepositoryStructure(): void
    {
        if ($structure = $this->option('structure')) {
            if (in_array($structure, ['flat', 'domains'])) {
                $this->chosenStructure = $structure;
                $this->info("Using {$structure} repository structure");

                return;
            } else {
                $this->warn("Invalid structure option: {$structure}. Valid options are: flat, domains");
            }
        }

        $this->info('Choose your repository structure:');
        $this->line('  1. flat - All repositories in app/Restify/');
        $this->line('  2. domains - Grouped by model in app/Restify/Domains/{Model}/');

        $choice = $this->choice(
            'Which structure do you prefer?',
            ['flat', 'domains'],
            'flat'
        );

        $this->chosenStructure = $choice;
        $this->info("Selected: {$choice} structure");
    }

    protected function showPreview(Collection $models): void
    {
        $this->newLine();
        $this->comment('📋 Preview of repositories to be generated:');
        $this->line('═══════════════════════════════════════════════════════');

        // Show models found
        $this->info("🔍 Found {$models->count()} models:");
        $models->each(function ($model) {
            $tableName = $model['table'] ?? 'unknown';
            $fieldsCount = count($model['fields']);
            $this->line("   • {$model['name']} (table: {$tableName}, {$fieldsCount} fields)");
        });

        $this->newLine();

        // Show structure configuration
        $this->info('📂 Repository configuration:');
        $this->line("   Structure: {$this->chosenStructure}");
        $this->line('   Base namespace: App\\Restify'.($this->chosenStructure === 'domains' ? '\\Domains' : ''));
        $this->line('   Force overwrite: '.($this->option('force') ? 'Yes' : 'No'));

        $this->newLine();

        // Show files that will be created
        $this->info('📄 Repositories that will be generated:');
        $models->each(function ($model, $index) {
            $path = $this->getRepositoryPath($model);
            $this->line('   '.($index + 1).". {$path}");
        });

        $this->newLine();

        // Show a sample repository
        $firstModel = $models->first();
        if ($firstModel) {
            $this->info('📝 Sample repository preview:');
            $this->line('   ┌─────────────────────────────────────────────────────┐');
            $this->line("   │ class {$firstModel['name']}Repository extends Repository");
            $this->line('   │ {');
            $this->line("   │     public static string \$model = {$firstModel['class']}::class;");
            $this->line('   │');
            $this->line('   │     public function fields(RestifyRequest $request): array');
            $this->line('   │     {');
            $this->line('   │         return [');

            // Show first few fields
            $sampleFields = collect($firstModel['fields'])->take(4);
            foreach ($sampleFields as $field) {
                if ($field['restify_field'] && $field['restify_field'] !== 'id()') {
                    $this->line("   │             {$field['restify_field']},");
                }
            }

            $totalFields = count(array_filter($firstModel['fields'], fn ($f) => $f['restify_field']));
            if ($totalFields > 4) {
                $this->line('   │             # ... '.($totalFields - 4).' more fields');
            }

            $this->line('   │         ];');
            $this->line('   │     }');
            $this->line('   │ }');

            if ($models->count() > 1) {
                $this->line('   │');
                $this->line('   │ # ... plus '.($models->count() - 1).' more repositories');
            }

            $this->line('   └─────────────────────────────────────────────────────┘');
        }

        // Show existing files warning
        $existingRepositories = $this->checkExistingRepositories($models);
        if ($existingRepositories->isNotEmpty() && ! $this->option('force')) {
            $this->newLine();
            $this->warn('⚠️  The following repositories already exist and will be skipped:');
            $existingRepositories->each(fn ($repo) => $this->line("   • {$repo}"));
            $this->line('   Use --force to overwrite existing repositories.');
        }

        $this->line('═══════════════════════════════════════════════════════');
        $this->newLine();
    }

    protected function checkExistingRepositories(Collection $models): Collection
    {
        return $models->filter(function ($model) {
            $path = $this->getRepositoryFilePath($model);

            return $this->files->exists($path);
        })->map(function ($model) {
            return $model['name'].'Repository';
        });
    }

    protected function generateRepositoryForModel(array $modelData): string
    {
        $repositoryPath = $this->getRepositoryFilePath($modelData);

        // Check if file exists and we're not forcing
        if ($this->files->exists($repositoryPath) && ! $this->option('force')) {
            return 'skipped';
        }

        // Ensure directory exists
        $directory = dirname($repositoryPath);
        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        // Generate repository content
        $content = $this->generateRepositoryContent($modelData);

        // Write the file
        $this->files->put($repositoryPath, $content);

        return 'generated';
    }

    protected function generateRepositoryContent(array $modelData): string
    {
        $className = $modelData['name'].'Repository';
        $modelClass = $modelData['class'];
        $namespace = $this->getRepositoryNamespace($modelData);

        // Generate fields
        $fields = $this->generateFields($modelData);

        return <<<PHP
<?php

namespace {$namespace};

use {$modelClass};
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

class {$className} extends Repository
{
    public static string \$model = {$modelData['name']}::class;

    public function fields(RestifyRequest \$request): array
    {
        return [
{$fields}
        ];
    }
}
PHP;
    }

    protected function generateFields(array $modelData): string
    {
        $fieldsCode = [];

        // Always add ID field first
        $fieldsCode[] = '            id(),';

        // Add other fields
        foreach ($modelData['fields'] as $field) {
            if ($field['restify_field'] && $field['restify_field'] !== 'id()' && $field['name'] !== 'id') {
                $fieldsCode[] = '            '.$field['restify_field'].',';
            }
        }

        // If no fields were generated, add a comment
        if (count($fieldsCode) === 1) {
            $fieldsCode[] = '            // Add your fields here';
        }

        return implode("\n", $fieldsCode);
    }

    protected function getRepositoryNamespace(array $modelData): string
    {
        $baseNamespace = 'App\\Restify';

        if ($this->chosenStructure === 'domains') {
            return $baseNamespace.'\\Domains\\'.$modelData['name'];
        }

        return $baseNamespace;
    }

    protected function getRepositoryPath(array $modelData): string
    {
        $namespace = $this->getRepositoryNamespace($modelData);
        $className = $modelData['name'].'Repository';

        return str_replace('App\\', 'app/', str_replace('\\', '/', $namespace)).'/'.$className.'.php';
    }

    protected function getRepositoryFilePath(array $modelData): string
    {
        return base_path($this->getRepositoryPath($modelData));
    }

    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Overwrite existing repositories'],
            ['skip-preview', null, InputOption::VALUE_NONE, 'Skip preview and generate immediately'],
            ['structure', null, InputOption::VALUE_OPTIONAL, 'Repository structure (flat|domains)'],
            ['only', null, InputOption::VALUE_OPTIONAL, 'Only generate repositories for specific models (comma-separated)'],
            ['except', null, InputOption::VALUE_OPTIONAL, 'Exclude specific models (comma-separated)'],
        ];
    }
}
