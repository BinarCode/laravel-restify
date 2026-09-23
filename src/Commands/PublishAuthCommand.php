<?php

namespace Binaryk\LaravelRestify\Commands;

use Binaryk\LaravelRestify\RestifyApplicationServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use PhpToken;
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

        if ($actions !== null) {
            foreach (array_diff($actions, $actionsToPublish) as $alreadyPublished) {
                $this->components->warn("{$alreadyPublished} is already published, skipped.");
            }
        }

        if ($actionsToPublish === []) {
            $this->components->warn('Nothing left to publish.');

            if ($actions === null) {
                return self::SUCCESS;
            }
        } else {
            $this->registerRoutes($actionsToPublish, $apiRoutesPath);
        }

        $this->publishControllers($actions)
            ->publishNotifications($actions);

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

        $updated = substr_replace($contents, $replacement, $call['offset'], $call['length']);

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
     * @return array{0: array{offset: int, length: int}, 1: list<string>}
     *
     * @throws RuntimeException
     */
    private function parseRestifyAuthCall(string $contents): array
    {
        [$offset, $length, $arguments] = $this->locateRestifyAuthCall($contents);

        return [['offset' => $offset, 'length' => $length], $this->parseExistingRemainingActions($arguments)];
    }

    /**
     * Finds the real `Route::restifyAuth(...)` call by tokenizing the file and masking out
     * every comment first, so a call that only appears inside a `//` or `/* *\/` comment is
     * never mistaken for the real one, and a `Str::replaceFirst()`-style text search (which
     * would rewrite the first textual match, comment or not) is never needed.
     *
     * @return array{0: int, 1: int, 2: string}
     *
     * @throws RuntimeException
     */
    private function locateRestifyAuthCall(string $contents): array
    {
        $masked = $contents;

        foreach (PhpToken::tokenize($contents) as $token) {
            if ($token->is([T_COMMENT, T_DOC_COMMENT])) {
                $masked = substr_replace(
                    $masked,
                    preg_replace('/[^\r\n]/', ' ', $token->text) ?? str_repeat(' ', strlen($token->text)),
                    $token->pos,
                    strlen($token->text)
                );
            }
        }

        if (! preg_match_all('/^[ \t]*\K\\\\?Route::restifyAuth\(\s*(.*?)\s*\);/ms', $masked, $matches, PREG_OFFSET_CAPTURE)) {
            throw new RuntimeException(
                'No Route::restifyAuth() call was found in routes/api.php. Add Route::restifyAuth(); and try again.'
            );
        }

        if (count($matches[0]) > 1) {
            throw new RuntimeException(
                'Multiple Route::restifyAuth() calls were found in routes/api.php. This command can only merge into a single call. Combine them manually and try again.'
            );
        }

        [$text, $offset] = $matches[0][0];

        return [$offset, strlen($text), $matches[1][0][0]];
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

        if (! preg_match('/^actions:\s*(\[.*\])\s*,?$/s', $arguments, $matches)) {
            throw new RuntimeException(
                "routes/api.php's Route::restifyAuth() call has a prefix or an argument this command cannot safely merge new routes with. Update it manually, or reset it to Route::restifyAuth(); first."
            );
        }

        return $this->parseActionsArray($matches[1]);
    }

    /**
     * Parses a PHP array literal of action names, e.g. `['login', 'register']`,
     * `["login", "register"]`, or the same spread across multiple lines (CRLF or LF),
     * with or without a trailing comma before the closing bracket. Tokenizes the
     * literal itself rather than pattern-matching it, so it only ever accepts a flat
     * list of quoted strings. An action name containing a backslash is rejected
     * outright instead of being unescaped: a stub action is a plain identifier, and
     * there is no legitimate reason for one to contain one.
     *
     * @return list<string>
     *
     * @throws RuntimeException
     */
    private function parseActionsArray(string $array): array
    {
        $parseError = "routes/api.php's Route::restifyAuth(actions: ...) call could not be parsed. Update it manually, or reset it to Route::restifyAuth(); first.";

        $tokens = array_values(array_filter(
            token_get_all('<?php '.trim($array).';'),
            fn (array|string $token): bool => ! is_array($token) || ! in_array($token[0], [T_WHITESPACE, T_OPEN_TAG], true)
        ));

        if (($tokens[0] ?? null) !== '[') {
            throw new RuntimeException($parseError);
        }

        $actions = [];
        $expectingValue = true;
        $closed = false;
        $count = count($tokens);
        $i = 1;

        for (; $i < $count; $i++) {
            $token = $tokens[$i];

            if ($token === ']') {
                $closed = true;
                $i++;

                break;
            }

            if ($expectingValue) {
                if (! is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING) {
                    throw new RuntimeException($parseError);
                }

                $action = substr($token[1], 1, -1);

                if (str_contains($action, '\\')) {
                    throw new RuntimeException($parseError);
                }

                $actions[] = $action;
                $expectingValue = false;

                continue;
            }

            if ($token !== ',') {
                throw new RuntimeException($parseError);
            }

            $expectingValue = true;
        }

        if (! $closed) {
            throw new RuntimeException($parseError);
        }

        for (; $i < $count; $i++) {
            if ($tokens[$i] !== ';') {
                throw new RuntimeException($parseError);
            }
        }

        return $actions;
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
