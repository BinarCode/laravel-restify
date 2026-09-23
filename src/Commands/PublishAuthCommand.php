<?php

namespace Binaryk\LaravelRestify\Commands;

use Binaryk\LaravelRestify\RestifyApplicationServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;

class PublishAuthCommand extends Command
{
    protected $signature = 'restify:auth {--actions= : Comma-separated list of actions to publish}';

    protected $description = 'Publish auth controllers & notification.';

    /**
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
     * @var array<string, string>
     */
    private const ALIASES = [
        'verify' => 'verifyEmail',
    ];

    public function __construct(
        private readonly Filesystem $files = new Filesystem
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $apiRoutesPath = base_path('routes/api.php');

        if (! $this->files->exists($apiRoutesPath)) {
            $this->components->error(
                "routes/api.php does not exist. Run 'php artisan install:api' first, then re-run this command."
            );

            return self::FAILURE;
        }

        $actions = $this->requestedActions();

        if ($actions !== null && ($error = $this->validateActions($actions)) !== null) {
            $this->components->error($error);

            return self::FAILURE;
        }

        try {
            $actionsToPublish = $this->resolveActionsToPublish($actions, $apiRoutesPath);
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($actionsToPublish === []) {
            $this->components->warn('Nothing left to publish.');

            return self::SUCCESS;
        }

        $this->registerRoutes($actionsToPublish, $apiRoutesPath);

        $this->publishControllers($actionsToPublish)
            ->publishNotifications($actionsToPublish);

        $this->info('Auth controllers published.');

        return self::SUCCESS;
    }

    /**
     * @param  list<string>|null  $actions
     */
    public function publishControllers(?array $actions = null): self
    {
        $actions ??= $this->requestedActions();

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
    public function publishNotifications(?array $actions = null): self
    {
        $actions ??= $this->requestedActions();

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
        $this->files->ensureDirectoryExists(app_path($path));

        return $this;
    }

    /**
     * @param  list<string>|null  $actions
     */
    protected function copyDirectory(string $path, string $stubDirectory, string $format, ?array $actions = []): self
    {
        foreach ($this->files->allFiles(__DIR__.$stubDirectory) as $file) {
            $action = $this->canonicalizeAction(Str::before($file->getFilename(), 'Controller.stub'));

            if (! empty($actions) && ! in_array($action, $actions, true)) {
                continue;
            }

            $fullPath = app_path($path.Str::replaceLast('.stub', $format, $file->getFilename()));

            $this->files->copy($file->getPathname(), $fullPath);

            $this->setNamespace($stubDirectory, $file->getFilename(), $path, $fullPath);
        }

        return $this;
    }

    /**
     * @param  list<string>  $actionsToPublish
     */
    protected function registerRoutes(array $actionsToPublish, ?string $apiRoutesPath = null): self
    {
        $apiRoutesPath ??= base_path('routes/api.php');

        $contents = $this->files->get($apiRoutesPath);

        [$call, $existingRemaining] = $this->parseRestifyAuthCall($contents);

        $replacement = $this->getRemainingActionsString($actionsToPublish, $existingRemaining);

        $updated = Str::replaceFirst($call, $replacement, $contents);

        $routeStubs = $this->getRouteStubs($actionsToPublish);

        $this->files->put($apiRoutesPath, $updated."\n".$routeStubs);

        return $this;
    }

    /**
     * @param  list<string>|null  $actions
     * @return list<string>
     *
     * @throws RuntimeException
     */
    private function resolveActionsToPublish(?array $actions, string $apiRoutesPath): array
    {
        [, $existingRemaining] = $this->parseRestifyAuthCall($this->files->get($apiRoutesPath));

        $actionsToPublish = $actions === null
            ? array_intersect(array_keys(self::ACTIONS), $existingRemaining)
            : array_intersect($actions, $existingRemaining);

        return array_values($actionsToPublish);
    }

    /**
     * @return array{0: string, 1: list<string>}
     *
     * @throws RuntimeException
     */
    private function parseRestifyAuthCall(string $contents): array
    {
        if (! preg_match('/^[ \t]*\K(?!\/\/)Route::restifyAuth\(\s*(.*?)\s*\);/ms', $contents, $matches)) {
            throw new RuntimeException(
                'No Route::restifyAuth() call was found in routes/api.php. Add Route::restifyAuth(); and try again.'
            );
        }

        return [$matches[0], $this->parseExistingRemainingActions($matches[1])];
    }

    /**
     * @param  list<string>|null  $actions
     */
    protected function getRouteStubs(?array $actions = null): string
    {
        $actions ??= $this->requestedActions();

        $stubDirectory = __DIR__.'/stubs/Routes/';

        $routeStubs = '';

        foreach (self::ACTIONS as $action => $stubs) {
            if ($this->isRequested($action, $actions)) {
                $routeStubs .= $this->files->get($stubDirectory.$stubs['route']);
            }
        }

        return $routeStubs;
    }

    /**
     * @param  list<string>|null  $actions
     * @param  list<string>  $baseline
     */
    protected function getRemainingActionsString(?array $actions = null, array $baseline = RestifyApplicationServiceProvider::AUTH_ACTIONS): string
    {
        $publishedActions = $actions === null
            ? array_keys(self::ACTIONS)
            : array_intersect($actions, array_keys(self::ACTIONS));

        $remainingActions = array_values(array_diff($baseline, $publishedActions));

        $items = implode(', ', array_map(
            fn (string $action): string => "'".addslashes($action)."'",
            $remainingActions
        ));

        return "Route::restifyAuth(actions: [{$items}]);";
    }

    /**
     * @return list<string>
     *
     * @throws RuntimeException
     */
    private function parseExistingRemainingActions(string $arguments): array
    {
        $arguments = trim($arguments);

        if ($arguments === '') {
            return RestifyApplicationServiceProvider::AUTH_ACTIONS;
        }

        if (! preg_match('/^actions:\s*(\[.*\])$/s', $arguments, $matches)) {
            throw new RuntimeException(
                "routes/api.php's Route::restifyAuth() call has a prefix or an argument this command cannot safely merge new routes with. Update it manually, or reset it to Route::restifyAuth(); first."
            );
        }

        return $this->parseActionsArray($matches[1]);
    }

    /**
     * Parses a PHP array literal of action names, e.g. `['login', 'register']`,
     * `["login", "register"]`, or the same spread across multiple lines, with or
     * without a trailing comma before the closing bracket.
     *
     * @return list<string>
     *
     * @throws RuntimeException
     */
    private function parseActionsArray(string $array): array
    {
        $parseError = "routes/api.php's Route::restifyAuth(actions: ...) call could not be parsed. Update it manually, or reset it to Route::restifyAuth(); first.";

        if (! preg_match('/^\[(?<items>.*)\]$/s', trim($array), $matches)) {
            throw new RuntimeException($parseError);
        }

        $items = trim($matches['items']);

        if (str_ends_with($items, ',')) {
            $items = trim(substr($items, 0, -1));
        }

        if ($items === '') {
            return [];
        }

        $stringToken = '\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*"';

        if (! preg_match_all('/'.$stringToken.'/', $items, $tokenMatches)) {
            throw new RuntimeException($parseError);
        }

        $withoutTokens = preg_replace('/'.$stringToken.'/', '', $items) ?? '';
        $remainder = trim(preg_replace('/[\s,]+/', '', $withoutTokens) ?? '');

        if ($remainder !== '') {
            throw new RuntimeException($parseError);
        }

        return array_map(
            fn (string $token): string => stripcslashes(substr($token, 1, -1)),
            $tokenMatches[0]
        );
    }

    /**
     * @param  list<string>  $actions
     */
    private function validateActions(array $actions): ?string
    {
        if ($actions === []) {
            return 'No valid actions were found in --actions.';
        }

        foreach ($actions as $action) {
            if ($action === 'logout') {
                return "The 'logout' action is registered by the macro; there is nothing to publish for it.";
            }

            if (! array_key_exists($action, self::ACTIONS)) {
                return "Unknown action [{$action}].";
            }
        }

        return null;
    }

    private function publishStub(string $stubDirectory, string $stubFileName, string $path, string $format): void
    {
        $fullPath = app_path($path.Str::replaceLast('.stub', $format, $stubFileName));

        if ($this->files->exists($fullPath)) {
            $this->components->warn(basename($fullPath).' already exists, skipped.');

            return;
        }

        $this->files->copy(__DIR__.$stubDirectory.'/'.$stubFileName, $fullPath);

        $this->setNamespace($stubDirectory, $stubFileName, $path, $fullPath);
    }

    protected function setNamespace(string $stubDirectory, string $fileName, string $path, string $fullPath): string
    {
        $path = substr(str_replace('/', '\\', $path), 0, -1);

        return $this->files->put($fullPath, str_replace(
            '{{namespace}}',
            $this->laravel->getNamespace().$path,
            $this->files->get(__DIR__.$stubDirectory.'/'.$fileName)
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
