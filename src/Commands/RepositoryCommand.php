<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;

class RepositoryCommand extends GeneratorCommand
{
    use ConfirmableTrait;

    protected $name = 'restify:repository';

    protected $description = 'Create a new repository class';

    protected $type = 'Repository';

    protected $confirmedModelClass = null;

    public function handle()
    {
        // Inform user about detected path pattern
        $existingRepositoryPath = $this->findExistingRepositoryPath();
        if ($existingRepositoryPath) {
            $this->info('Detected repository pattern: '.$existingRepositoryPath['pattern']);
            $this->info('Repository will be created in: '.$existingRepositoryPath['namespace']);
        }

        // Check if file already exists and ask for confirmation
        $name = $this->qualifyClass($this->getNameInput());
        $path = $this->getPath($name);

        if ($this->files->exists($path) && ! $this->option('force')) {
            $this->error('Repository already exists at: '.$path);

            if (! $this->confirm('Do you want to override it?')) {
                $this->info('Repository creation cancelled.');

                return false;
            }

            // Set force option to true since user confirmed override
            $this->input->setOption('force', true);
        }

        if (parent::handle() === false && ! $this->option('force')) {
            return false;
        }

        $this->callSilent('restify:base-repository', [
            'name' => 'Repository',
        ]);

        if ($this->option('all')) {
            $this->input->setOption('factory', true);
            $this->input->setOption('model', true);
            $this->input->setOption('policy', true);
            $this->input->setOption('table', true);
        }

        if ($this->option('policy')) {
            $this->buildPolicy();
        }

        if ($this->option('model')) {
            $this->buildModel();
        }

        if ($this->option('table')) {
            $this->buildMigration();
        }

        if ($this->option('factory')) {
            $this->buildFactory();
        }
    }

    /**
     * Build the class with the given name.
     * This method should return the file class content.
     *
     * @param  string  $name
     * @return string
     *
     * @throws FileNotFoundException
     */
    protected function buildClass($name)
    {
        if (Str::endsWith($name, 'Repository') === false) {
            $name .= 'Repository';
        }

        // Ensure we have a valid model before building
        $modelClass = $this->guessQualifiedModelName();
        if (! $modelClass) {
            throw new \RuntimeException('Could not determine the model class.');
        }

        $stub = parent::buildClass($name);

        // Replace DummyRootNamespace placeholder
        $rootNamespace = rtrim($this->rootNamespace(), '\\');
        $stub = str_replace('DummyRootNamespace', $rootNamespace, $stub);

        $stub = $this->replaceModel($stub, $this->guessBaseModelClass());

        // Generate fields if not disabled
        if (! $this->option('no-fields')) {
            $stub = $this->replaceFields($stub);
            $stub = $this->replaceRelationships($stub);
        } else {
            // Use default id() field
            $stub = str_replace('{{ fields }}', '            id(),', $stub);
            $stub = str_replace('{{ relationships }}', '', $stub);
            $stub = str_replace('{{ relationshipImports }}', '', $stub);
        }

        // Clean up any double backslashes in the final stub
        $stub = str_replace('\\\\', '\\', $stub);

        return $stub;
    }

    protected function replaceModel($stub, $class)
    {
        $model = str_replace(['DummyClass', '{{ modelBase }}', '{{modelBase}}'], "$class::class", $stub);

        $qualifiedModel = $this->guessQualifiedModelName();
        // Ensure we use the correct namespace separator
        $qualifiedModel = str_replace('\\\\', '\\', $qualifiedModel);

        return str_replace(['DummyClass', '{{ model }}', '{{model}}'], $qualifiedModel.';', $model);
    }

    protected function guessBaseModelClass()
    {
        return class_basename($this->guessQualifiedModelName());
    }

    protected function guessQualifiedModelName()
    {
        // If we already have a confirmed model from discovery, use it
        if ($this->confirmedModelClass) {
            return $this->confirmedModelClass;
        }

        $model = Str::singular(class_basename(Str::before($this->getNameInput(), 'Repository')));
        $defaultModelClass = str_replace('/', '\\', $this->rootNamespace().'/Models/'.$model);

        // If default model exists, use it
        if (class_exists($defaultModelClass)) {
            return $defaultModelClass;
        }

        // Try to find the model in the app folder
        $foundModels = $this->findModelInApp($model);

        if (count($foundModels) === 1) {
            // Found exactly one model
            $foundModel = $foundModels[0];
            if ($this->confirm("Found model: {$foundModel}. Do you want to use this model?")) {
                $this->confirmedModelClass = $foundModel;

                return $foundModel;
            }
        } elseif (count($foundModels) > 1) {
            // Found multiple models
            $this->info('Found multiple models matching "'.$model.'":');
            foreach ($foundModels as $index => $foundModel) {
                $this->line(($index + 1).'. '.$foundModel);
            }

            $choice = $this->ask('Which model would you like to use? (Enter number or 0 to specify manually)');

            if ($choice > 0 && $choice <= count($foundModels)) {
                $this->confirmedModelClass = $foundModels[$choice - 1];

                return $this->confirmedModelClass;
            }
        }

        // No model found or user chose to specify manually
        $result = $this->promptForModel();
        $this->confirmedModelClass = $result;

        return $result;
    }

    protected function buildMigration()
    {
        $table = Str::snake(Str::pluralStudly(class_basename($this->guessQualifiedModelName())));

        $guessMigration = 'Create'.Str::studly($table).'Table';

        if (class_exists($guessMigration) === false) {
            $migration = Str::snake($guessMigration);
            $yes = $this->confirm("Do you want to generate the migration [{$migration}]?");

            if ($yes) {
                $this->call('make:migration', [
                    'name' => $migration,
                    '--create' => $table,
                ]);
            }
        }
    }

    protected function buildPolicy()
    {
        $this->call('restify:policy', [
            'name' => $this->guessBaseModelClass(),
        ]);

        return $this;
    }

    protected function buildModel()
    {
        $model = $this->guessQualifiedModelName();

        if ($model && class_exists($model) === false) {
            $yes = $this->confirm("Do you want to generate the model [{$model}]?");

            if ($yes) {
                $this->call('make:model', ['name' => str_replace('\\\\', '\\', $model)]);
            }
        }
    }

    protected function buildFactory()
    {
        $factory = Str::studly(class_basename($this->guessQualifiedModelName()));

        $this->call('make:factory', [
            'name' => "{$factory}Factory",
            '--model' => str_replace('\\\\', '\\', $this->guessQualifiedModelName()),
        ]);
    }

    protected function getStub()
    {
        return __DIR__.'/stubs/repository.stub';
    }

    protected function getPath($name)
    {
        if (Str::endsWith($name, 'Repository') === false) {
            $name .= 'Repository';
        }

        // Try to find existing repositories to determine the pattern
        $existingRepositoryPath = $this->findExistingRepositoryPath();

        if ($existingRepositoryPath && $existingRepositoryPath['pattern']) {
            // Apply the discovered pattern
            $modelBaseName = Str::before(class_basename($name), 'Repository');
            $patternPath = $this->applyPathPattern($modelBaseName, $existingRepositoryPath['pattern']);

            // Build the namespace path, avoiding duplication
            $namespaceParts = [];
            $baseNamespace = str_replace($this->rootNamespace().'\\', '', $existingRepositoryPath['namespace']);
            if ($baseNamespace) {
                $namespaceParts[] = $baseNamespace;
            }

            if ($patternPath && ! empty($patternPath)) {
                $namespaceParts[] = str_replace('\\', '/', $patternPath);
            }

            $namespaceParts[] = class_basename($name);

            return $this->laravel['path'].'/'.implode('/', $namespaceParts).'.php';
        }

        return parent::getPath($name);
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        // Try to find existing repositories to determine the pattern
        $existingRepositoryPath = $this->findExistingRepositoryPath();

        if ($existingRepositoryPath) {
            $namespace = $existingRepositoryPath['namespace'];

            // Apply pattern for the current model if needed
            if ($existingRepositoryPath['pattern'] && $existingRepositoryPath['pattern'] !== 'flat') {
                $modelBaseName = Str::before(class_basename($this->getNameInput()), 'Repository');
                $patternPath = $this->applyPathPattern($modelBaseName, $existingRepositoryPath['pattern']);

                if ($patternPath && ! empty($patternPath)) {
                    // Only add pattern path if it doesn't already exist in namespace
                    $patternPathNormalized = str_replace('/', '\\', $patternPath);
                    if (! Str::endsWith($namespace, $patternPathNormalized)) {
                        $namespace .= '\\'.$patternPathNormalized;
                    }
                }
            }

            return $namespace;
        }

        // Fallback to default
        return rtrim($rootNamespace, '\\').'\\Restify';
    }

    protected function replaceFields($stub)
    {
        $fields = $this->generateFieldsFromSchema();

        if (empty($fields)) {
            // Fallback to id() if no fields could be generated
            $fields = ['            id(),'];
        }

        return str_replace('{{ fields }}', implode("\n", $fields), $stub);
    }

    protected function replaceRelationships($stub)
    {
        $relationships = $this->generateRelationshipsFromSchema();

        if (empty($relationships['relations'])) {
            // No relationships, remove the placeholders
            $stub = str_replace('{{ relationships }}', '', $stub);
            $stub = str_replace('{{ relationshipImports }}', '', $stub);
        } else {
            // Add the static include method
            $includeMethod = "\n    public static function include(): array\n";
            $includeMethod .= "    {\n";
            $includeMethod .= "        return [\n";
            $includeMethod .= implode("\n", $relationships['relations']);
            $includeMethod .= "\n        ];\n";
            $includeMethod .= "    }\n";

            $stub = str_replace('{{ relationships }}', $includeMethod, $stub);

            // Add necessary imports
            $imports = array_unique($relationships['imports']);
            $importStatements = implode("\n", array_map(function ($import) {
                return "use $import;";
            }, $imports));

            $stub = str_replace('{{ relationshipImports }}', $importStatements, $stub);
        }

        return $stub;
    }

    protected function generateFieldsFromSchema()
    {
        $modelClass = $this->guessQualifiedModelName();

        // Check if model class exists
        if (! $modelClass || ! class_exists($modelClass)) {
            return [];
        }

        try {
            $model = new $modelClass;
            $table = $model->getTable();

            if (! Schema::hasTable($table)) {
                return [];
            }

            $columns = Schema::getColumnListing($table);
            $fields = [];

            foreach ($columns as $column) {
                $field = $this->generateFieldForColumn($table, $column);
                if ($field !== null) {
                    $fields[] = $field;
                }
            }

            return $fields;
        } catch (\Exception $e) {
            // If anything fails, return empty array
            return [];
        }
    }

    protected function generateFieldForColumn($table, $column)
    {
        // Skip ID field as it's handled separately
        if ($column === 'id') {
            return '            id(),';
        }

        // Get column type using Schema builder
        $columnType = Schema::getColumnType($table, $column);

        // Skip foreign key columns - they will be handled as relationships
        if (Str::endsWith($column, '_id') && $column !== 'id') {
            return null;
        }

        // Start building the field
        $field = "            field('$column')";

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

        // Check if column is nullable
        try {
            // Use raw PDO to check nullable status
            $connection = Schema::getConnection();
            $dbName = $connection->getDatabaseName();
            $results = $connection->select('
                SELECT IS_NULLABLE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?
            ', [$dbName, $table, $column]);

            if (! empty($results) && $results[0]->IS_NULLABLE === 'YES') {
                $field .= '->nullable()';
            }
        } catch (\Exception $e) {
            // Ignore if we can't determine nullable status
        }

        // Handle timestamps and other readonly fields
        if (in_array($column, ['created_at', 'updated_at', 'deleted_at', 'email_verified_at'])) {
            $field .= '->readonly()';
        }

        // Add required validation for non-nullable fields (except special cases)
        if (! Str::contains($field, 'nullable()') &&
            ! in_array($column, ['created_at', 'updated_at', 'deleted_at', 'remember_token']) &&
            ! Str::contains($field, 'readonly()')) {
            $field .= '->required()';
        }

        $field .= ',';

        return $field;
    }

    protected function findModelInApp($searchName)
    {
        $models = [];

        try {
            $finder = new Finder;
            $finder->files()
                ->in(app_path())
                ->name('*.php')
                ->notPath('Http')
                ->notPath('Console')
                ->notPath('Exceptions')
                ->notPath('Providers');

            foreach ($finder as $file) {
                $relativePath = str_replace(app_path().DIRECTORY_SEPARATOR, '', $file->getRealPath());
                $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
                $className = 'App\\'.str_replace(['/', '.php'], ['\\', ''], $relativePath);

                // Check if it's a valid model class
                if (class_exists($className) && $this->isModel($className)) {
                    $baseName = class_basename($className);
                    // Check if the class name matches our search
                    if (strcasecmp($baseName, $searchName) === 0 ||
                        strcasecmp($baseName, Str::plural($searchName)) === 0 ||
                        strcasecmp($baseName, Str::singular($searchName)) === 0) {
                        $models[] = $className;
                    }
                }
            }
        } catch (\Exception $e) {
            // If finder fails, return empty array
        }

        return array_unique($models);
    }

    protected function isModel($className)
    {
        try {
            $reflection = new \ReflectionClass($className);

            // Check if it's an instantiable class
            if (! $reflection->isInstantiable()) {
                return false;
            }

            // Check if it extends Eloquent Model
            return $reflection->isSubclassOf('Illuminate\\Database\\Eloquent\\Model');
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function promptForModel()
    {
        $this->warn('Model not found.');

        while (true) {
            $modelName = $this->ask('Please enter the model name (without namespace, e.g., User, Employee)');

            if (! $modelName) {
                $this->error('Model name is required.');

                continue;
            }

            // Clean the input
            $modelName = trim($modelName);
            $modelName = str_replace(['/', '\\'], '', $modelName);

            // Search for the model
            $foundModels = $this->searchForModelClass($modelName);

            if (count($foundModels) === 0) {
                $this->error("No model found with name '{$modelName}'.");
                if (! $this->confirm('Would you like to try again?')) {
                    exit(1);
                }

                continue;
            }

            if (count($foundModels) === 1) {
                $this->confirmedModelClass = $foundModels[0];
                $this->info("Using model: {$foundModels[0]}");

                return $foundModels[0];
            }

            // Multiple models found
            $this->info('Found multiple models:');
            foreach ($foundModels as $index => $model) {
                $this->line(($index + 1).'. '.$model);
            }

            $choice = $this->ask('Which model would you like to use? (Enter number)');

            if ($choice > 0 && $choice <= count($foundModels)) {
                $this->confirmedModelClass = $foundModels[$choice - 1];
                $this->info("Using model: {$foundModels[$choice - 1]}");

                return $foundModels[$choice - 1];
            }

            $this->error('Invalid choice.');
        }
    }

    protected function searchForModelClass($modelName)
    {
        $models = [];

        // First, check common locations
        $commonLocations = [
            $this->rootNamespace().'\\Models\\'.$modelName,
            $this->rootNamespace().'\\'.$modelName,
            'App\\Models\\'.$modelName,
            'App\\'.$modelName,
        ];

        foreach ($commonLocations as $location) {
            if (class_exists($location) && $this->isModel($location)) {
                $models[] = $location;
            }
        }

        // If not found in common locations, search the entire app folder
        if (empty($models)) {
            $models = $this->findModelInApp($modelName);
        }

        return array_unique($models);
    }

    protected function generateRelationshipsFromSchema()
    {
        $modelClass = $this->guessQualifiedModelName();

        if (! $modelClass || ! class_exists($modelClass)) {
            return ['relations' => [], 'imports' => []];
        }

        try {
            $model = new $modelClass;
            $table = $model->getTable();

            if (! Schema::hasTable($table)) {
                return ['relations' => [], 'imports' => []];
            }

            $columns = Schema::getColumnListing($table);
            $relations = [];
            $imports = [];

            // Generate BelongsTo relationships
            foreach ($columns as $column) {
                if (Str::endsWith($column, '_id') && $column !== 'id') {
                    $relationshipData = $this->generateBelongsToRelationship($column);
                    if ($relationshipData) {
                        $relations[] = $relationshipData['relation'];
                        if (! empty($relationshipData['imports'])) {
                            $imports = array_merge($imports, $relationshipData['imports']);
                        }
                    }
                }
            }

            // Generate HasMany relationships
            $hasManyRelationships = $this->detectHasManyRelationships($modelClass, $table);
            foreach ($hasManyRelationships as $relationshipData) {
                $relations[] = $relationshipData['relation'];
                if (! empty($relationshipData['imports'])) {
                    $imports = array_merge($imports, $relationshipData['imports']);
                }
            }

            // Always include the base relationship field imports
            $imports[] = 'Binaryk\\LaravelRestify\\Fields\\BelongsTo';
            $imports[] = 'Binaryk\\LaravelRestify\\Fields\\HasMany';

            return ['relations' => $relations, 'imports' => array_unique($imports)];
        } catch (\Exception $e) {
            return ['relations' => [], 'imports' => []];
        }
    }

    protected function generateBelongsToRelationship($column)
    {
        $relationName = Str::camel(Str::beforeLast($column, '_id'));
        $modelName = Str::studly(Str::beforeLast($column, '_id'));

        // Try to find the related model
        $relatedModel = $this->findRelatedModel($modelName);
        if (! $relatedModel) {
            // If we can't find the model, still generate the relationship
            return [
                'relation' => "            BelongsTo::make('$relationName'),",
                'imports' => [],
            ];
        }

        // Try to find the repository for the related model
        $repositoryClass = $this->findRepositoryForModel($relatedModel);

        if ($repositoryClass) {
            return [
                'relation' => "            BelongsTo::make('$relationName', $repositoryClass::class),",
                'imports' => [$repositoryClass],
            ];
        } else {
            return [
                'relation' => "            BelongsTo::make('$relationName'),",
                'imports' => [],
            ];
        }
    }

    protected function detectHasManyRelationships($modelClass, $tableName)
    {
        $relationships = [];
        $modelBaseName = class_basename($modelClass);
        $expectedForeignKey = Str::snake($modelBaseName).'_id';
        $pluralName = Str::plural(Str::snake($modelBaseName));

        // Get all tables in the database
        $tables = Schema::getAllTables();

        foreach ($tables as $tableObj) {
            // Get the table name (varies by database driver)
            $otherTable = is_object($tableObj) ?
                ($tableObj->name ?? $tableObj->tablename ?? $tableObj->Tables_in_database ?? reset($tableObj)) :
                $tableObj;

            if ($otherTable === $tableName) {
                continue;
            }

            try {
                if (Schema::hasColumn($otherTable, $expectedForeignKey)) {
                    // Found a table with a foreign key to this model
                    $relationName = Str::camel(Str::plural(Str::beforeLast($otherTable, '_'.$pluralName)));
                    if ($relationName === 'plural') {
                        $relationName = Str::camel($otherTable);
                    }

                    // Try to find the model for this table
                    $relatedModelName = Str::studly(Str::singular($otherTable));
                    $relatedModel = $this->findRelatedModel($relatedModelName);

                    if ($relatedModel) {
                        $repositoryClass = $this->findRepositoryForModel($relatedModel);

                        if ($repositoryClass) {
                            $relationships[] = [
                                'relation' => "            HasMany::make('$relationName', $repositoryClass::class),",
                                'imports' => [$repositoryClass],
                            ];
                        } else {
                            $relationships[] = [
                                'relation' => "            HasMany::make('$relationName'),",
                                'imports' => [],
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                // Skip this table if there's an error
                continue;
            }
        }

        return $relationships;
    }

    protected function findRelatedModel($modelName)
    {
        // Common locations to check
        $possibleClasses = [
            $this->rootNamespace().'\\Models\\'.$modelName,
            $this->rootNamespace().'\\'.$modelName,
            'App\\Models\\'.$modelName,
            'App\\'.$modelName,
        ];

        foreach ($possibleClasses as $class) {
            if (class_exists($class) && $this->isModel($class)) {
                return $class;
            }
        }

        // If not found in common locations, search the app folder
        $foundModels = $this->findModelInApp($modelName);
        if (! empty($foundModels)) {
            return $foundModels[0];
        }

        return null;
    }

    protected function findRepositoryForModel($modelClass)
    {
        $modelBaseName = class_basename($modelClass);
        $repositoryName = $modelBaseName.'Repository';

        // First, check if we have a discovered pattern
        $existingRepositoryPath = $this->findExistingRepositoryPath();

        if ($existingRepositoryPath && $existingRepositoryPath['pattern']) {
            // Build repository class based on discovered pattern
            $namespace = $existingRepositoryPath['namespace'];
            $pattern = $existingRepositoryPath['pattern'];

            $possibleRepositories = [];

            switch ($pattern) {
                case 'grouped-by-model':
                    $possibleRepositories[] = $namespace.'\\'.Str::plural($modelBaseName).'\\'.$repositoryName;
                    $possibleRepositories[] = $namespace.'\\'.$modelBaseName.'\\'.$repositoryName;
                    break;

                case 'domain-driven':
                    $possibleRepositories[] = $namespace.'\\Domains\\'.$modelBaseName.'\\'.$repositoryName;
                    $possibleRepositories[] = $namespace.'\\Domain\\'.$modelBaseName.'\\'.$repositoryName;
                    break;

                case 'flat':
                default:
                    $possibleRepositories[] = $namespace.'\\'.$repositoryName;
                    break;
            }

            foreach ($possibleRepositories as $repositoryClass) {
                if (class_exists($repositoryClass)) {
                    return $repositoryClass;
                }
            }
        }

        // Fallback to common repository locations
        $commonRepositories = [
            $this->rootNamespace().'\\Restify\\'.$repositoryName,
            'App\\Restify\\'.$repositoryName,
            $this->rootNamespace().'\\Http\\Restify\\'.$repositoryName,
            'App\\Http\\Restify\\'.$repositoryName,
        ];

        foreach ($commonRepositories as $repositoryClass) {
            if (class_exists($repositoryClass)) {
                return $repositoryClass;
            }
        }

        // Try to find repository anywhere in the app
        try {
            $finder = new Finder;
            $finder->files()
                ->in(app_path())
                ->name($repositoryName.'.php');

            foreach ($finder as $file) {
                $relativePath = str_replace(app_path().DIRECTORY_SEPARATOR, '', $file->getRealPath());
                $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
                $relativePath = str_replace('.php', '', $relativePath);

                $possibleClass = $this->rootNamespace().'\\'.str_replace('/', '\\', $relativePath);
                if (class_exists($possibleClass)) {
                    return $possibleClass;
                }
            }
        } catch (\Exception $e) {
            // Ignore finder errors
        }

        return null;
    }

    protected function findExistingRepositoryPath()
    {
        try {
            // First, try to find repositories in App/Restify directory
            $restifyPath = app_path('Restify');
            if (is_dir($restifyPath)) {
                $restifyRepositoryPaths = $this->findRepositoriesInPath($restifyPath, 'Restify');
                if (! empty($restifyRepositoryPaths)) {
                    // Prefer repositories with more specific paths (deeper nesting)
                    usort($restifyRepositoryPaths, function ($a, $b) {
                        return $b['depth'] <=> $a['depth'];
                    });

                    return $restifyRepositoryPaths[0];
                }
            }

            // If not found in Restify, search the entire app folder
            $allRepositoryPaths = $this->findRepositoriesInPath(app_path());

            if (! empty($allRepositoryPaths)) {
                // Prefer repositories with more specific paths (deeper nesting)
                usort($allRepositoryPaths, function ($a, $b) {
                    return $b['depth'] <=> $a['depth'];
                });

                return $allRepositoryPaths[0];
            }
        } catch (\Exception $e) {
            // If finder fails, return null
        }

        return null;
    }

    protected function findRepositoriesInPath($searchPath, $baseFolder = '')
    {
        $repositoryPaths = [];

        try {
            $finder = new Finder;
            $finder->files()
                ->in($searchPath)
                ->name('*Repository.php')
                ->notPath('vendor')
                ->notPath('tests');

            foreach ($finder as $file) {
                $fullPath = str_replace(app_path().DIRECTORY_SEPARATOR, '', $file->getRealPath());
                $fullPath = str_replace(DIRECTORY_SEPARATOR, '/', $fullPath);

                // Skip if it's a base repository class at root level
                if (basename($fullPath) === 'Repository.php' && substr_count($fullPath, '/') === 0) {
                    continue;
                }

                // Extract the pattern
                $pathParts = explode('/', $fullPath);
                $fileName = array_pop($pathParts);
                $repositoryName = str_replace('.php', '', $fileName);
                $modelName = str_replace('Repository', '', $repositoryName);

                // Analyze the path structure
                $pattern = $this->analyzeRepositoryPath($pathParts, $modelName);

                if ($pattern) {
                    $namespace = $this->rootNamespace().'\\'.str_replace('/', '\\', implode('/', $pathParts));
                    $repositoryPaths[] = [
                        'path' => $fullPath,
                        'namespace' => $namespace,
                        'pattern' => $pattern,
                        'depth' => count($pathParts),
                        'isInRestify' => $baseFolder === 'Restify' || str_starts_with($fullPath, 'Restify/'),
                    ];
                }
            }
        } catch (\Exception $e) {
            // If finder fails, return empty array
        }

        return $repositoryPaths;
    }

    protected function analyzeRepositoryPath($pathParts, $modelName)
    {
        if (empty($pathParts)) {
            return null;
        }

        // Check for common patterns
        $pathString = implode('/', $pathParts);

        // Pattern 1: App/Restify/Users/UserRepository (grouped by model)
        if (Str::contains($pathString, $modelName) || Str::contains($pathString, Str::plural($modelName))) {
            return 'grouped-by-model';
        }

        // Pattern 2: App/Restify/Domains/User/UserRepository
        if (Str::contains($pathString, 'Domains/'.$modelName) || Str::contains($pathString, 'Domain/'.$modelName)) {
            return 'domain-driven';
        }

        // Pattern 3: App/Restify/Admin/UserRepository or App/Restify/Api/UserRepository
        if (preg_match('/\/(Admin|Api|Backend|Frontend)\//', $pathString)) {
            return 'module-based';
        }

        // Pattern 4: Simple flat structure App/Restify/UserRepository
        if (count($pathParts) === 1 && $pathParts[0] === 'Restify') {
            return 'flat';
        }

        // Pattern 5: Check if the last folder before the repository matches a pattern
        $lastFolder = end($pathParts);
        if ($lastFolder === $modelName || $lastFolder === Str::plural($modelName)) {
            return 'grouped-by-model';
        }

        return 'custom';
    }

    protected function applyPathPattern($modelName, $pattern)
    {
        switch ($pattern) {
            case 'grouped-by-model':
                // Use plural form of model name as folder
                return Str::plural($modelName);

            case 'domain-driven':
                // Use Domains/ModelName structure
                return 'Domains\\'.$modelName;

            case 'module-based':
                // Try to detect which module based on the model name
                // This is a simple heuristic, could be improved
                return '';

            case 'flat':
            default:
                // No additional path
                return '';
        }
    }

    protected function getOptions()
    {
        return [
            ['all', 'a', InputOption::VALUE_NONE, 'Generate a migration, factory, and controller for the repository'],
            ['model', 'm', InputOption::VALUE_NONE, 'The model class being represented.'],
            ['factory', 'f', InputOption::VALUE_NONE, 'Create a new factory for the repository model.'],
            ['policy', 'p', InputOption::VALUE_NONE, 'Create a new policy for the repository model.'],
            ['table', 't', InputOption::VALUE_NONE, 'Create a new migration table file for the repository model.'],
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the model already exists.'],
            ['no-fields', null, InputOption::VALUE_NONE, 'Do not generate fields from model schema.'],
        ];
    }
}
