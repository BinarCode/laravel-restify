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

    protected function copyDirectory(string $path, string $stubDirectory, string $format, ?array $actions = []): self
    {
        $filesystem = new Filesystem();

        collect($filesystem->allFiles(__DIR__.$stubDirectory))
            ->filter(function (SplFileInfo $file) use ($actions) {
                if (empty($actions)) {
                    return true;
                }

                $actionName = Str::before($file->getFilename(), 'Controller');

                return in_array($actionName, $actions, true) || in_array(Str::lower($actionName), $actions, true);
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
            'ForgotPassword' => 'forgotPasswordRoute.stub',
            'resetPassword' => 'forgotPasswordRoute.stub',
            'ResetPassword' => 'resetPasswordRoute.stub',
            'verifyEmail' => 'verifyRoute.stub',
            'verify' => 'verifyRoute.stub',
        ];

        $routeStubs = '';

        foreach ($routes as $action => $routeStub) {
            if (! $actions || in_array($action, $actions, true)) {
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
}
