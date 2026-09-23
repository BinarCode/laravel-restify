<?php

namespace Binaryk\LaravelRestify\Commands;

use Binaryk\LaravelRestify\RestifyApplicationServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class PublishAuthCommand extends Command
{
    protected $signature = 'restify:auth {--actions= : Comma-separated list of actions to publish}';

    protected $description = 'Publish auth controllers & notification.';

    /**
     * The actions this command can discretely publish a controller and a route for.
     *
     * @var array<string, array{controller: string, route: string}>
     */
    private const ACTIONS = [
        'login' => ['controller' => 'LoginController.stub', 'route' => 'loginRoute.stub'],
        'register' => ['controller' => 'RegisterController.stub', 'route' => 'registerRoute.stub'],
        'forgotPassword' => ['controller' => 'ForgotPasswordController.stub', 'route' => 'forgotPasswordRoute.stub'],
        'resetPassword' => ['controller' => 'ResetPasswordController.stub', 'route' => 'resetPasswordRoute.stub'],
        'verifyEmail' => ['controller' => 'VerifyController.stub', 'route' => 'verifyRoute.stub'],
    ];

    /**
     * Accepted alternate spellings, mapped to the canonical action they mean.
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        'verify' => 'verifyEmail',
    ];

    public function handle(): int
    {
        $apiRoutesPath = base_path('routes/api.php');

        if (! file_exists($apiRoutesPath)) {
            $this->components->error(
                "routes/api.php does not exist. Run 'php artisan install:api' first, then re-run this command."
            );

            return self::FAILURE;
        }

        $actions = $this->requestedActions();

        $this->publishControllers($actions)
            ->publishNotifications($actions)
            ->registerRoutes($actions, $apiRoutesPath);

        $this->info('Auth controllers published.');

        return self::SUCCESS;
    }

    /**
     * @param  list<string>|null  $actions
     */
    public function publishControllers(?array $actions): self
    {
        $path = 'Http/Controllers/Restify/Auth/';

        $this->checkDirectory($path);

        foreach (self::ACTIONS as $action => $stubs) {
            if ($this->isRequested($action, $actions)) {
                $this->publishStub('/stubs/Auth', $stubs['controller'], $path, '.php');
            }
        }

        return $this;
    }

    /**
     * @param  list<string>|null  $actions
     */
    public function publishNotifications(?array $actions): self
    {
        if (! $this->isRequested('forgotPassword', $actions)) {
            return $this;
        }

        $path = 'Notifications/Restify/';

        $this->checkDirectory($path);

        $this->publishStub('/stubs/Notifications', 'ForgotPasswordNotification.stub', $path, '.php');

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
    protected function registerRoutes(?array $actions, string $apiRoutesPath): self
    {
        $initial = file_get_contents($apiRoutesPath);

        $remainingActionsString = $this->getRemainingActionsString($actions);
        $initial = str($initial)->replace('Route::restifyAuth();', $remainingActionsString)->toString();

        $routeStubs = $this->getRouteStubs($actions);

        file_put_contents($apiRoutesPath, $initial."\n".$routeStubs);

        return $this;
    }

    /**
     * @param  list<string>|null  $actions
     */
    protected function getRouteStubs(?array $actions): string
    {
        $stubDirectory = __DIR__.'/stubs/Routes/';

        $routeStubs = '';

        foreach (self::ACTIONS as $action => $stubs) {
            if ($this->isRequested($action, $actions)) {
                $routeStubs .= file_get_contents($stubDirectory.$stubs['route']);
            }
        }

        return $routeStubs;
    }

    /**
     * @param  list<string>|null  $actions
     */
    protected function getRemainingActionsString(?array $actions): string
    {
        $publishedActions = $actions === null
            ? array_keys(self::ACTIONS)
            : array_intersect($actions, array_keys(self::ACTIONS));

        $remainingActions = array_diff(RestifyApplicationServiceProvider::AUTH_ACTIONS, $publishedActions);

        if (empty($remainingActions)) {
            return '';
        }

        return 'Route::restifyAuth(actions: '.json_encode(array_values($remainingActions)).');';
    }

    private function publishStub(string $stubDirectory, string $stubFileName, string $path, string $format): void
    {
        $filesystem = new Filesystem;

        $filesystem->copy(
            __DIR__.$stubDirectory.'/'.$stubFileName,
            $fullPath = app_path($path.Str::replaceLast('.stub', $format, $stubFileName))
        );

        $this->setNamespace($stubDirectory, $stubFileName, $path, $fullPath);
    }

    private function setNamespace(string $stubDirectory, string $fileName, string $path, string $fullPath): string
    {
        $path = substr(str_replace('/', '\\', $path), 0, -1);

        return file_put_contents($fullPath, str_replace(
            '{{namespace}}',
            $this->laravel->getNamespace().$path,
            file_get_contents(__DIR__.$stubDirectory.'/'.$fileName)
        ));
    }

    /**
     * @return list<string>|null
     */
    private function requestedActions(): ?array
    {
        $option = $this->option('actions');

        if (! is_string($option) || trim($option) === '') {
            return null;
        }

        $actions = array_filter(array_map(trim(...), explode(',', $option)), fn (string $action): bool => $action !== '');

        return array_values(array_map($this->canonicalizeAction(...), $actions));
    }

    private function canonicalizeAction(string $action): string
    {
        $lower = Str::lower($action);

        if (isset(self::ALIASES[$lower])) {
            return self::ALIASES[$lower];
        }

        foreach (RestifyApplicationServiceProvider::AUTH_ACTIONS as $canonicalAction) {
            if (Str::lower($canonicalAction) === $lower) {
                return $canonicalAction;
            }
        }

        return $action;
    }

    /**
     * @param  list<string>|null  $actions
     */
    private function isRequested(string $action, ?array $actions): bool
    {
        return $actions === null || in_array($action, $actions, true);
    }
}
