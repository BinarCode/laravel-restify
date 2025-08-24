<?php

namespace Binaryk\LaravelRestify\Commands;

use Binaryk\LaravelRestify\Restify;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GraphqlGenerateCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'restify:graphql:generate
                            {--force : Overwrite existing files}
                            {--output-path= : Output directory for generated files}
                            {--schema-file= : Schema file name}
                            {--resolvers : Generate resolver classes}
                            {--skip-preview : Skip preview and confirmation}';

    protected $description = 'Generate GraphQL schema and resolvers from Restify repositories';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $this->info('🚀 Generating GraphQL schema from Restify repositories...');

        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        // Setup authentication mocking for console context
        $this->setupAuthenticationMocking();

        $repositories = $this->getRepositories();

        if ($repositories->isEmpty()) {
            $this->warn('No repositories found. Make sure you have repositories in your app/Restify directory.');

            return self::FAILURE;
        }

        $outputPath = $this->getOutputPath();
        $schemaFile = $this->getSchemaFile();

        // Show preview unless skipped
        if (! $this->option('skip-preview')) {
            $this->showPreview($repositories, $outputPath, $schemaFile);

            if (! $this->confirm('Do you want to proceed with generating these files?')) {
                $this->comment('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        $this->info('Generating GraphQL files...');
        $this->ensureDirectoryExists($outputPath);

        // Generate schema
        $schemaContent = $this->generateSchema($repositories);
        $this->writeFile($outputPath.'/'.$schemaFile, $schemaContent);

        $this->info("✅ Schema generated: {$outputPath}/{$schemaFile}");

        // Generate resolvers if requested
        if ($this->option('resolvers')) {
            $resolverCount = $this->generateResolvers($repositories, $outputPath);
            $this->info("✅ Generated {$resolverCount} resolver classes in: {$outputPath}/Resolvers/");
        }

        $this->newLine();
        $this->comment('🎉 GraphQL generation complete!');
        $this->comment('Next steps:');
        $this->comment('  1. Install lighthouse/lighthouse if not already installed');
        $this->comment('  2. Configure lighthouse to use the generated schema');
        $this->comment('  3. Register the resolvers in your GraphQL setup');

        return self::SUCCESS;
    }

    protected function getRepositories(): Collection
    {
        // Ensure repositories are loaded
        Restify::ensureRepositoriesLoaded();

        return collect(Restify::$repositories)
            ->filter(fn ($repo) => class_exists($repo))
            ->map(fn ($repo) => class_basename($repo));
    }

    protected function showPreview(Collection $repositories, string $outputPath, string $schemaFile): void
    {
        $this->newLine();
        $this->comment('📋 Preview of files to be generated:');
        $this->line('═══════════════════════════════════════════════════════');

        // Show repositories found
        $this->info("🔍 Found {$repositories->count()} repositories:");
        $repositories->each(fn ($repo) => $this->line("   • {$repo}"));

        $this->newLine();

        // Show output configuration
        $this->info('📂 Output configuration:');
        $this->line("   Output directory: {$outputPath}");
        $this->line("   Schema file: {$schemaFile}");
        $this->line('   Generate resolvers: '.($this->option('resolvers') ? 'Yes' : 'No'));
        $this->line('   Force overwrite: '.($this->option('force') ? 'Yes' : 'No'));

        $this->newLine();

        // Show files that will be created
        $this->info('📄 Files that will be generated:');
        $this->line("   1. {$outputPath}/{$schemaFile}");

        if ($this->option('resolvers')) {
            $this->line("   2. Resolvers directory: {$outputPath}/Resolvers/");
            $repositories->each(function ($repo, $index) use ($outputPath) {
                $this->line('      '.($index + 3).". {$outputPath}/Resolvers/{$repo}Resolver.php");
            });
        }

        $this->newLine();

        // Show a sample of what will be generated
        $this->info('📝 Sample GraphQL schema preview:');
        $this->line('   ┌─────────────────────────────────────────────────────┐');

        // Generate a small sample of the schema for preview
        $firstRepo = $repositories->first();
        if ($firstRepo) {
            $typeName = $this->getGraphQLTypeName($firstRepo);
            $repositoryClass = $this->getRepositoryClass($firstRepo);
            $uriKey = $this->getRepositoryUriKey($repositoryClass);

            $this->line("   │ type {$typeName} {");
            $this->line('   │   id: ID!');

            // Try to show a few fields from the first repository
            try {
                if (class_exists($repositoryClass)) {
                    $repository = new $repositoryClass;
                    $request = app(\Binaryk\LaravelRestify\Http\Requests\RestifyRequest::class);
                    $fieldCollection = $repository->collectFields($request);

                    $sampleFields = collect($fieldCollection)->take(4);
                    foreach ($sampleFields as $field) {
                        $fieldName = $field->attribute ?? $field->resolveAttribute();

                        // Skip ID since we already show it
                        if ($fieldName === 'id') {
                            continue;
                        }

                        $graphqlType = $this->mapFieldToGraphQLType($field);
                        $this->line("   │   {$fieldName}: {$graphqlType}");
                    }

                    $totalFields = count($fieldCollection);
                    if ($totalFields > 4) {
                        $this->line('   │   # ... '.($totalFields - 4).' more fields');
                    }
                }
            } catch (\Exception $e) {
                $this->line('   │   # Fields will be auto-detected from repository');
            }

            $this->line('   │ }');
            $this->line('   │');
            $this->line('   │ type Query {');
            $this->line("   │   {$uriKey}(id: ID!): {$typeName}");
            $this->line("   │   {$uriKey}List(first: Int, page: Int): [{$typeName}!]!");
            $this->line('   │ }');
            $this->line('   │');
            $this->line('   │ type Mutation {');
            $this->line("   │   create{$typeName}(input: {$typeName}Input!): {$typeName}!");
            $this->line("   │   update{$typeName}(id: ID!, input: {$typeName}Input!): {$typeName}!");
            $this->line("   │   delete{$typeName}(id: ID!): Boolean!");
            $this->line('   │ }');

            if ($repositories->count() > 1) {
                $this->line('   │');
                $this->line('   │ # ... plus '.($repositories->count() - 1).' more types');
            }
        }

        $this->line('   └─────────────────────────────────────────────────────┘');

        // Show existing files warning
        $existingFiles = $this->checkExistingFiles($outputPath, $schemaFile, $repositories);
        if ($existingFiles->isNotEmpty() && ! $this->option('force')) {
            $this->newLine();
            $this->warn('⚠️  The following files already exist and will be overwritten:');
            $existingFiles->each(fn ($file) => $this->line("   • {$file}"));
        }

        $this->line('═══════════════════════════════════════════════════════');
        $this->newLine();
    }

    protected function checkExistingFiles(string $outputPath, string $schemaFile, Collection $repositories): Collection
    {
        $existingFiles = collect();

        // Check schema file
        if ($this->files->exists($outputPath.'/'.$schemaFile)) {
            $existingFiles->push($outputPath.'/'.$schemaFile);
        }

        // Check resolver files if resolvers option is enabled
        if ($this->option('resolvers')) {
            $resolverPath = $outputPath.'/Resolvers';
            $repositories->each(function ($repo) use ($resolverPath, $existingFiles) {
                $resolverFile = $resolverPath.'/'.$repo.'Resolver.php';
                if ($this->files->exists($resolverFile)) {
                    $existingFiles->push($resolverFile);
                }
            });
        }

        return $existingFiles;
    }

    protected function generateSchema(Collection $repositories): string
    {
        $types = [];
        $queries = [];
        $mutations = [];

        foreach ($repositories as $repositoryName) {
            $repositoryClass = $this->getRepositoryClass($repositoryName);
            $typeName = $this->getGraphQLTypeName($repositoryName);
            $uriKey = $this->getRepositoryUriKey($repositoryClass);

            // Generate GraphQL type
            $types[] = $this->generateType($repositoryClass, $typeName);

            // Generate queries
            $queries[] = "    {$uriKey}(id: ID!): {$typeName}";
            $queries[] = "    {$uriKey}List(first: Int = 15, page: Int = 1): [{$typeName}!]!";

            // Generate mutations
            $mutations[] = "    create{$typeName}(input: {$typeName}Input!): {$typeName}!";
            $mutations[] = "    update{$typeName}(id: ID!, input: {$typeName}Input!): {$typeName}!";
            $mutations[] = "    delete{$typeName}(id: ID!): Boolean!";

            // Generate input type
            $types[] = $this->generateInputType($repositoryClass, $typeName);
        }

        return $this->buildSchemaContent($types, $queries, $mutations);
    }

    protected function generateType(string $repositoryClass, string $typeName): string
    {
        if (! class_exists($repositoryClass)) {
            return "type {$typeName} {\n    id: ID!\n}";
        }

        $repository = new $repositoryClass;
        $request = app(\Binaryk\LaravelRestify\Http\Requests\RestifyRequest::class);

        $fields = ['    id: ID!'];

        try {
            // Use collectFields to get all fields properly (like MCP does)
            $fieldCollection = $repository->collectFields($request);

            foreach ($fieldCollection as $field) {
                $fieldName = $field->attribute ?? $field->resolveAttribute();

                // Skip the ID field since we already added it
                if ($fieldName === 'id') {
                    continue;
                }

                $graphqlType = $this->mapFieldToGraphQLType($field);
                $fields[] = "    {$fieldName}: {$graphqlType}";
            }
        } catch (\Exception $e) {
            // Fallback if fields can't be resolved
            $this->warn("Could not resolve fields for {$repositoryClass}: {$e->getMessage()}");
        }

        $fieldsString = implode("\n", $fields);

        return "type {$typeName} {\n{$fieldsString}\n}";
    }

    protected function generateInputType(string $repositoryClass, string $typeName): string
    {
        if (! class_exists($repositoryClass)) {
            return "input {$typeName}Input {\n    # Add input fields here\n}";
        }

        $repository = new $repositoryClass;
        $request = app(\Binaryk\LaravelRestify\Http\Requests\RestifyRequest::class);

        $fields = [];

        try {
            // Use collectFields to get all fields properly (like MCP does)
            $fieldCollection = $repository->collectFields($request);

            foreach ($fieldCollection as $field) {
                $fieldName = $field->attribute ?? $field->resolveAttribute();
                if ($fieldName === 'id') {
                    continue;
                } // Skip ID for input

                $graphqlType = $this->mapFieldToGraphQLType($field, true);
                $fields[] = "    {$fieldName}: {$graphqlType}";
            }
        } catch (\Exception $e) {
            $fields[] = '    # Could not resolve fields automatically: '.$e->getMessage();
        }

        if (empty($fields)) {
            $fields[] = '    # Add input fields here';
        }

        $fieldsString = implode("\n", $fields);

        return "input {$typeName}Input {\n{$fieldsString}\n}";
    }

    protected function mapFieldToGraphQLType($field, bool $isInput = false): string
    {
        $fieldClass = get_class($field);
        $fieldClassName = class_basename($fieldClass);

        // Use the field's built-in type guessing if available
        if (method_exists($field, 'guessFieldType')) {
            $fieldType = $field->guessFieldType();

            switch ($fieldType) {
                case 'boolean':
                    return 'Boolean';
                case 'number':
                case 'integer':
                    return 'Int';
                case 'array':
                    return $isInput ? '[String!]' : '[String!]';
                case 'object':
                    return $isInput ? 'JSON' : 'JSON';
                case 'string':
                default:
                    return 'String';
            }
        }

        // Map specific field classes to GraphQL types
        $typeMap = [
            // Basic field types
            'Field' => 'String',
            'OrganicField' => 'String',
            'BaseField' => 'String',

            // Text fields
            'Text' => 'String',
            'Textarea' => 'String',
            'Email' => 'String',
            'Password' => 'String',
            'Url' => 'String',

            // Numeric fields
            'Number' => 'Int',
            'Integer' => 'Int',
            'Float' => 'Float',
            'Decimal' => 'Float',

            // Boolean fields
            'Boolean' => 'Boolean',
            'Toggle' => 'Boolean',

            // Date/Time fields
            'Date' => 'String',
            'DateTime' => 'String',
            'Time' => 'String',
            'Timestamp' => 'String',

            // File fields
            'File' => 'String',
            'Image' => 'String',

            // Special fields
            'Json' => 'JSON',
            'Select' => 'String',
            'MultiSelect' => '[String!]',
            'Searchable' => 'String',

            // Relationship fields
            'BelongsTo' => $isInput ? 'ID' : ($this->shouldGenerateRelationshipType($field) ? $this->getRelationshipTypeName($field) : 'String'),
            'HasMany' => $isInput ? '[ID!]' : ($this->shouldGenerateRelationshipType($field) ? '['.$this->getRelationshipTypeName($field).'!]' : '[String!]'),
            'HasOne' => $isInput ? 'ID' : ($this->shouldGenerateRelationshipType($field) ? $this->getRelationshipTypeName($field) : 'String'),
            'BelongsToMany' => $isInput ? '[ID!]' : '[String!]',
            'MorphTo' => $isInput ? 'ID' : 'String',
            'MorphOne' => $isInput ? 'ID' : 'String',
            'MorphMany' => $isInput ? '[ID!]' : '[String!]',
            'MorphToMany' => $isInput ? '[ID!]' : '[String!]',
        ];

        // Check for exact class name match first
        if (isset($typeMap[$fieldClassName])) {
            return $typeMap[$fieldClassName];
        }

        // Check for partial matches
        foreach ($typeMap as $pattern => $graphqlType) {
            if (Str::contains($fieldClass, $pattern)) {
                return $graphqlType;
            }
        }

        return 'String'; // Default fallback
    }

    protected function shouldGenerateRelationshipType($field): bool
    {
        // For now, keep it simple and just return basic types
        // In the future, this could be enhanced to generate nested relationship types
        return false;
    }

    protected function getRelationshipTypeName($field): string
    {
        // This would be used if we decide to generate nested relationship types
        return 'String';
    }

    protected function generateResolvers(Collection $repositories, string $outputPath): int
    {
        $resolverPath = $outputPath.'/Resolvers';
        $this->ensureDirectoryExists($resolverPath);

        $count = 0;
        foreach ($repositories as $repositoryName) {
            $resolverContent = $this->generateResolverClass($repositoryName);
            $resolverFile = $resolverPath.'/'.$repositoryName.'Resolver.php';
            $this->writeFile($resolverFile, $resolverContent);
            $count++;
        }

        return $count;
    }

    protected function generateResolverClass(string $repositoryName): string
    {
        $repositoryClass = $this->getRepositoryClass($repositoryName);
        $typeName = $this->getGraphQLTypeName($repositoryName);

        return <<<PHP
<?php

namespace App\GraphQL\Resolvers;

use {$repositoryClass};
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Http\Request;

class {$repositoryName}Resolver
{
    public function show(\$root, array \$args, \$context, \$info)
    {
        \$repository = new {$repositoryName}();
        \$model = \$repository::newModel()->findOrFail(\$args['id']);

        return \$repository->setModel(\$model)
            ->serializeForShow(RestifyRequest::createFrom(request()));
    }

    public function index(\$root, array \$args, \$context, \$info)
    {
        \$repository = new {$repositoryName}();
        \$query = \$repository::newModel()->query();

        // Apply pagination
        \$page = \$args['page'] ?? 1;
        \$perPage = \$args['first'] ?? 15;

        return \$query->paginate(\$perPage, ['*'], 'page', \$page)->items();
    }

    public function create(\$root, array \$args, \$context, \$info)
    {
        \$repository = new {$repositoryName}();
        \$request = RestifyRequest::createFrom(request());
        \$request->merge(\$args['input']);

        return \$repository->store(\$request);
    }

    public function update(\$root, array \$args, \$context, \$info)
    {
        \$repository = new {$repositoryName}();
        \$request = RestifyRequest::createFrom(request());
        \$request->merge(\$args['input']);

        return \$repository->update(\$request, \$args['id']);
    }

    public function delete(\$root, array \$args, \$context, \$info)
    {
        \$repository = new {$repositoryName}();
        \$request = RestifyRequest::createFrom(request());

        \$repository->destroy(\$request, \$args['id']);

        return true;
    }
}
PHP;
    }

    protected function buildSchemaContent(array $types, array $queries, array $mutations): string
    {
        $typesString = implode("\n\n", $types);
        $queriesString = implode("\n", $queries);
        $mutationsString = implode("\n", $mutations);

        return <<<GRAPHQL
# Auto-generated GraphQL schema from Restify repositories
# Generated on: {$this->getCurrentTimestamp()}

{$typesString}

type Query {
{$queriesString}
}

type Mutation {
{$mutationsString}
}
GRAPHQL;
    }

    protected function getRepositoryClass(string $repositoryName): string
    {
        return collect(Restify::$repositories)
            ->first(fn ($repo) => class_basename($repo) === $repositoryName);
    }

    protected function getGraphQLTypeName(string $repositoryName): string
    {
        return Str::singular(str_replace('Repository', '', $repositoryName));
    }

    protected function getRepositoryUriKey(string $repositoryClass): string
    {
        if (! class_exists($repositoryClass)) {
            return Str::camel(Str::plural(str_replace('Repository', '', class_basename($repositoryClass))));
        }

        return $repositoryClass::uriKey();
    }

    protected function getOutputPath(): string
    {
        return $this->option('output-path') ?: base_path('app/GraphQL');
    }

    protected function getSchemaFile(): string
    {
        return $this->option('schema-file') ?: 'schema.graphql';
    }

    protected function ensureDirectoryExists(string $path): void
    {
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }
    }

    protected function writeFile(string $path, string $content): void
    {
        if ($this->files->exists($path) && ! $this->option('force')) {
            if (! $this->confirm("File {$path} already exists. Overwrite?")) {
                return;
            }
        }

        $this->files->put($path, $content);
    }

    protected function getCurrentTimestamp(): string
    {
        return now()->format('Y-m-d H:i:s');
    }

    protected function setupAuthenticationMocking(): void
    {
        // Create a mock user that always returns true for permissions
        $mockUser = new class
        {
            public function can($permission): bool
            {
                return true;
            }

            public function cannot($permission): bool
            {
                return false;
            }

            public function id()
            {
                return 1;
            }

            public function getKey()
            {
                return 1;
            }

            public function __get($key)
            {
                return null;
            }

            public function __call($method, $parameters)
            {
                // Return true for any method call that might be permission related
                if (str_contains(strtolower($method), 'can') || str_contains(strtolower($method), 'able')) {
                    return true;
                }

                return null;
            }
        };

        // Mock Auth facade
        \Illuminate\Support\Facades\Auth::shouldReceive('user')
            ->andReturn($mockUser);

        \Illuminate\Support\Facades\Auth::shouldReceive('check')
            ->andReturn(true);

        \Illuminate\Support\Facades\Auth::shouldReceive('id')
            ->andReturn(1);

        request()->setUserResolver(function () use ($mockUser) {
            return $mockUser;
        });

        // Mock request user method
        app()->singleton('auth.user.mock', function () use ($mockUser) {
            return $mockUser;
        });

        // Extend RestifyRequest to return our mock user
        $originalMakeMethod = \Binaryk\LaravelRestify\Http\Requests\RestifyRequest::class.'::createFrom';

        // Create a custom request instance that returns our mock user
        app()->bind(\Binaryk\LaravelRestify\Http\Requests\RestifyRequest::class, function ($app) use ($mockUser) {
            $request = new \Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

            // Override the user method to return our mock
            $reflection = new \ReflectionClass($request);
            if ($reflection->hasMethod('setUserResolver')) {
                $request->setUserResolver(function () use ($mockUser) {
                    return $mockUser;
                });
            }

            return $request;
        });

        $this->info('✅ Authentication mocking enabled for console context');
    }
}
