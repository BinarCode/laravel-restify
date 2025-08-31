<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class SetupCommand extends Command
{
    protected $signature = 'restify:setup';

    protected $description = 'Should be run when you firstly install the package. It will setup everything for you.';

    public function handle()
    {
        $this->comment('Installing Laravel API...');
        $this->call('install:api');

        $this->comment('Publishing Restify Service Provider...');
        $this->callSilent('vendor:publish', ['--tag' => 'restify-provider']);

        $this->comment('Publishing Restify config...');
        $this->call('vendor:publish', [
            '--tag' => 'restify-config',
        ]);

        $this->comment('Publishing Restify migrations...');
        $this->call('vendor:publish', [
            '--tag' => 'restify-migrations',
        ]);

        $this->comment('Publishing Restify views...');
        $this->call('vendor:publish', [
            '--tag' => 'restify-views',
        ]);

        $this->registerRestifyServiceProvider();

        $this->comment('Generating User Repository...');
        $this->call(RepositoryCommand::class, ['name' => 'UserRepository']);

        if (! file_exists(app_path('Policies/UserPolicy.php'))) {
            app(Filesystem::class)->ensureDirectoryExists(app_path('Policies'));
            $this->call(PolicyCommand::class, ['name' => 'UserPolicy']);
        }

        $this->setAppNamespace();
        
        $this->configureUserModel();
        
        $this->createAiRoutesFile();

        $this->info('Restify setup successfully.');
    }

    /**
     * Register the Restify service provider in the bootstrap/providers.php file.
     *
     * @return void
     */
    protected function registerRestifyServiceProvider()
    {
        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());
        $providerClass = "{$namespace}\\Providers\\RestifyServiceProvider::class";

        $providersPath = base_path('bootstrap/providers.php');

        // Check if the providers.php file exists
        if (! file_exists($providersPath)) {
            $this->error('bootstrap/providers.php file not found. Make sure you are using Laravel 12.');

            return;
        }

        $content = file_get_contents($providersPath);

        // Check if the provider is already registered
        if (str_contains($content, $providerClass)) {
            $this->line('RestifyServiceProvider already registered.');

            return;
        }

        // Find the return statement
        $pattern = '/return\s+(\[.*?\]);/s';
        if (preg_match($pattern, $content, $matches)) {
            $providersArray = $matches[1];

            // Remove the closing bracket
            $providersArrayWithoutClosing = rtrim(trim($providersArray), ']');

            // Add our provider and close the array
            $newProvidersArray = $providersArrayWithoutClosing;

            // If the array is not empty and doesn't end with a comma, add a comma
            if (! empty($providersArrayWithoutClosing) && ! str_ends_with(trim($providersArrayWithoutClosing), ',')) {
                $newProvidersArray .= ',';
            }

            $newProvidersArray .= "\n    {$providerClass},\n]";

            // Replace the old array with the new one
            $newContent = preg_replace($pattern, "return {$newProvidersArray};", $content);

            file_put_contents($providersPath, $newContent);

            $this->line('RestifyServiceProvider registered in bootstrap/providers.php');
        } else {
            $this->error('Could not find the providers array in bootstrap/providers.php');
        }
    }

    /**
     * Set the proper application namespace on the installed files.
     *
     * @return void
     */
    protected function setAppNamespace()
    {
        $namespace = $this->laravel->getNamespace();

        $this->setAppNamespaceOn(app_path('Restify/UserRepository.php'), $namespace);
        $this->setAppNamespaceOn(app_path('Providers/RestifyServiceProvider.php'), $namespace);
    }

    /**
     * Set the namespace on the given file.
     *
     * @param  string  $file
     * @param  string  $namespace
     * @return void
     */
    protected function setAppNamespaceOn($file, $namespace)
    {
        file_put_contents($file, str_replace(
            'App\\',
            $namespace,
            file_get_contents($file)
        ));
    }

    /**
     * Configure the User model in the restify config.
     *
     * @return void
     */
    protected function configureUserModel()
    {
        $this->comment('Searching for User models in your application...');
        
        $userModels = $this->findUserModels();
        
        if (empty($userModels)) {
            $this->warn('No User models found in App namespace. Using default \\App\\Models\\User.');
            return;
        }
        
        if (count($userModels) === 1) {
            $selectedModel = $userModels[0];
            if ($this->confirm("Found User model: {$selectedModel}. Use this as your authentication model?", true)) {
                $this->updateUserModelConfig($selectedModel);
                $this->info("Updated restify config to use: {$selectedModel}");
            }
            return;
        }
        
        $this->info('Multiple User models found:');
        foreach ($userModels as $index => $model) {
            $this->line(" [{$index}] {$model}");
        }
        
        $choice = $this->ask('Please select the User model to use (enter the number)', '0');
        
        if (isset($userModels[$choice])) {
            $selectedModel = $userModels[$choice];
            $this->updateUserModelConfig($selectedModel);
            $this->info("Updated restify config to use: {$selectedModel}");
        } else {
            $this->warn('Invalid selection. Using default \\App\\Models\\User.');
        }
    }

    /**
     * Find User models in the App namespace.
     *
     * @return array
     */
    protected function findUserModels()
    {
        $appPath = app_path();
        $namespace = $this->laravel->getNamespace();
        $userModels = [];
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($appPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace($appPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $className = str_replace(['/', '.php'], ['\\', ''], $relativePath);
                $fqcn = $namespace . $className;
                
                if ($this->isUserModel($file->getPathname(), $className)) {
                    $userModels[] = $fqcn;
                }
            }
        }
        
        return $userModels;
    }

    /**
     * Check if a PHP file contains a User model.
     *
     * @param  string  $filePath
     * @param  string  $className
     * @return bool
     */
    protected function isUserModel($filePath, $className)
    {
        if (! str_contains(strtolower($className), 'user')) {
            return false;
        }
        
        $content = file_get_contents($filePath);
        
        return str_contains($content, 'extends Authenticatable') ||
               str_contains($content, 'use Authenticatable') ||
               str_contains($content, 'implements AuthenticatableContract') ||
               str_contains($content, 'HasApiTokens');
    }

    /**
     * Update the user_model configuration in restify config.
     *
     * @param  string  $userModel
     * @return void
     */
    protected function updateUserModelConfig($userModel)
    {
        $configPath = config_path('restify.php');
        
        if (! file_exists($configPath)) {
            $this->warn('restify.php config file not found.');
            return;
        }
        
        $content = file_get_contents($configPath);
        $escapedUserModel = addslashes($userModel);
        
        $pattern = "/'user_model'\s*=>\s*['\"].*?['\"]/";
        $replacement = "'user_model' => \"{$escapedUserModel}\"";
        
        $newContent = preg_replace($pattern, $replacement, $content);
        
        if ($newContent !== $content) {
            file_put_contents($configPath, $newContent);
        } else {
            $this->warn('Could not update user_model configuration.');
        }
    }

    /**
     * Create the ai.php routes file if it doesn't exist.
     *
     * @return void
     */
    protected function createAiRoutesFile()
    {
        $routesPath = base_path('routes');
        $aiRoutesFile = $routesPath . '/ai.php';
        
        if (file_exists($aiRoutesFile)) {
            $this->line('AI routes file already exists.');
            return;
        }
        
        app(Filesystem::class)->ensureDirectoryExists($routesPath);
        
        $content = "<?php\n\nuse Binaryk\\LaravelRestify\\MCP\\RestifyServer;\nuse Laravel\\Mcp\\Server\\Facades\\Mcp;\n\n// Restify MCP Server - provides AI agents access to your Restify repositories\n// Mcp::web('restify', RestifyServer::class)\n//     ->middleware(['auth:sanctum']); // Available at /mcp/restify\n\n// Mcp::local('restify', RestifyServer::class); // Start with ./artisan mcp:start restify\n\n// Example custom servers:\n// Mcp::web('demo', \\App\\Mcp\\Servers\\PublicServer::class); // Available at /mcp/demo\n// Mcp::local('demo', \\App\\Mcp\\Servers\\LocalServer::class); // Start with ./artisan mcp:start demo\n";
        
        file_put_contents($aiRoutesFile, $content);
        
        $this->info('Created routes/ai.php file for MCP server configuration.');
    }
}
