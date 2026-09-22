<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class PublishAuthCommand extends Command
{
    protected $signature = 'restify:auth {--actions= : Comma-separated list of actions to publish}';

    protected $description = 'Publish auth controllers & notification.';

    public function handle()
    {
        $actions = $this->option('actions') ? explode(',', $this->option('actions')) : null;

        $this
            ->publishControllers()
            ->publishNotifications()
            ->registerRoutes($actions);

        $this->info('Auth controllers published.');
    }

    public function publishControllers(): self
    {
        $path = 'Http/Controllers/Restify/Auth/';
        $stubDirectory = '/../Commands/stubs/Auth';
        $format = '.php';

        $actions = $this->option('actions') ? explode(',', $this->option('actions')) : null;

        $this->checkDirectory($path)
            ->copyDirectory($path, $stubDirectory, $format, $actions);

        return $this;
    }

    public function publishNotifications(): self
    {
        $actions = $this->option('actions') ? explode(',', $this->option('actions')) : null;

        if (! empty($actions) && ! in_array('forgotPassword', $actions)) {
            return $this;
        }

        $path = 'Notifications/Restify/';
        $stubDirectory = '/../Commands/stubs/Notifications';
        $format = '.php';

        $this->checkDirectory($path)
            ->copyDirectory($path, $stubDirectory, $format);

        return $this;
    }

    public function checkDirectory(string $path): self
    {
        if (! is_dir($directory = app_path($path))) {
            mkdir($directory, 0755, true);
        }

        return $this;
    }

    /**
     * @param  list<string>|null  $actions
     */
    protected function copyDirectory(string $path, string $stubDirectory, string $format, ?array $actions = []): self
    {
        $filesystem = new Filesystem;

        collect($filesystem->allFiles(__DIR__.$stubDirectory))
            ->filter(function (SplFileInfo $file) use ($actions) {
                $actionName = Str::before($file->getFilename(), 'Controller.stub');

                return $this->actionSelected($actionName, $actions);
            })
            ->each(function (SplFileInfo $file) use ($filesystem, $path, $format, $stubDirectory) {
                $filesystem->copy(
                    $file->getPathname(),
                    $fullPath = app_path($path.Str::replaceLast('.stub', $format, $file->getFilename()))
                );

                $this->setNamespace($stubDirectory, $file->getFilename(), $path, $fullPath);
            });

        return $this;
    }

    protected function setNamespace(string $stubDirectory, string $fileName, string $path, string $fullPath): string
    {
        $path = substr(str_replace('/', '\\', $path), 0, -1);

        return file_put_contents($fullPath, str_replace(
            '{{namespace}}',
            $this->laravel->getNamespace().$path,
            file_get_contents(__DIR__.$stubDirectory.'/'.$fileName)
        ));
    }

    protected function registerRoutes(?array $actions): self
    {
        $apiPath = base_path('routes/api.php');
        $initial = file_get_contents($apiPath);

        $remainingActionsString = $this->getRemainingActionsString($actions);
        $initial = str($initial)->replace('Route::restifyAuth();', $remainingActionsString)->toString();

        $file = fopen($apiPath, 'w');

        $routeStub = $this->getRouteStubs();

        fwrite($file, $initial."\n".$routeStub);

        fclose($file);

        return $this;
    }

    protected function getRouteStubs(): string
    {
        $actions = $this->option('actions') ? explode(',', $this->option('actions')) : null;

        $stubDirectory = __DIR__.'/stubs/Routes/';
        $routes = [
            'login' => 'loginRoute.stub',
            'register' => 'registerRoute.stub',
            'forgotPassword' => 'forgotPasswordRoute.stub',
            'resetPassword' => 'resetPasswordRoute.stub',
            'verifyEmail' => 'verifyRoute.stub',
            'verify' => 'verifyRoute.stub',
        ];

        $routeStubs = '';

        foreach ($routes as $action => $routeStub) {
            if ($this->actionSelected($action, $actions)) {
                $routeStubs .= file_get_contents($stubDirectory.$routeStub);
            }
        }

        return $routeStubs;
    }

    protected function getRemainingActionsString(?array $actions = null): string
    {
        $allActions = ['login', 'register', 'resetPassword', 'forgotPassword', 'verifyEmail'];

        if ($actions === null) {
            return 'Route::restifyAuth();';
        }

        $remainingActions = array_diff($allActions, $actions);

        if (empty($remainingActions)) {
            return '';
        }

        return 'Route::restifyAuth(actions: '.json_encode(array_values($remainingActions)).');';
    }

    /**
     * Determine if an action was requested, regardless of the casing it was typed with.
     *
     * @param  list<string>|null  $actions
     */
    private function actionSelected(string $action, ?array $actions): bool
    {
        if (! $actions) {
            return true;
        }

        $normalizedActions = array_map(
            fn (string $requestedAction): string => Str::lower($requestedAction),
            $actions
        );

        return in_array(Str::lower($action), $normalizedActions, true);
    }
}
