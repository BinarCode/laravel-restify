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
     * The route name each action registers, where it differs from the action itself.
     *
     * @var array<string, string>
     */
    private const ROUTE_NAMES = [
        'verifyEmail' => 'verify',
    ];

    /**
     * @var array<string, string>
     */
    private const ALIASES = [
        'verify' => 'verifyEmail',
    ];

    private bool $wroteAnyFile = false;

    public function __construct(
        private readonly Filesystem $files = new Filesystem
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->wroteAnyFile = false;

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

        $contents = $this->files->get($apiRoutesPath);

        try {
            $call = $this->parseRestifyAuthCall($contents);
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $requested = $actions ?? array_keys(self::ACTIONS);

        [$actionsToPublish, $alreadyPublished, $notInList] = $this->classifyRequestedActions(
            $requested,
            $contents,
            $call['existingRemaining'],
        );

        foreach ($alreadyPublished as $action) {
            $this->components->warn("{$action} is already published, skipped.");
        }

        foreach ($notInList as $action) {
            $this->components->warn("{$action} is not in the Route::restifyAuth() actions list; publishing it now.");
        }

        if ($actionsToPublish === []) {
            $this->components->warn('Nothing left to publish.');
        } else {
            try {
                $this->registerRoutes($actionsToPublish, $apiRoutesPath, $call + ['contents' => $contents]);
            } catch (RuntimeException $exception) {
                $this->components->error($exception->getMessage());

                return self::FAILURE;
            }
        }

        $this->publishControllers($requested)
            ->publishNotifications($requested);

        if ($this->wroteAnyFile) {
            $this->info('Auth controllers published.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<string>|null  $actions
     *
     * @phpstan-impure
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
     * @param  array{offset: int, length: int, existingRemaining: list<string>, contents?: string}|null  $parsedCall
     *
     * @throws RuntimeException
     *
     * @phpstan-impure
     */
    protected function registerRoutes(array $actionsToPublish, ?string $apiRoutesPath = null, ?array $parsedCall = null): self
    {
        $apiRoutesPath ??= base_path('routes/api.php');

        $contents = $parsedCall['contents'] ?? $this->files->get($apiRoutesPath);

        $call = $parsedCall ?? $this->parseRestifyAuthCall($contents);

        $replacement = $this->getRemainingActionsString($actionsToPublish, $call['existingRemaining']);

        $updated = substr_replace($contents, $replacement, $call['offset'], $call['length']);

        $updated = $this->ensureRouteFacadeImported($updated);

        $routeStubs = $this->getRouteStubs($actionsToPublish);

        $this->files->put($apiRoutesPath, $updated."\n".$routeStubs);

        $this->wroteAnyFile = true;

        return $this;
    }

    /**
     * Splits the requested actions into three buckets: ready to publish, already
     * published (a real route or its controller already exists even though the
     * action is no longer in the `Route::restifyAuth()` actions array), and
     * missing from that array without proof they were ever published, which
     * are published now rather than silently skipped.
     *
     * @param  list<string>  $requested
     * @param  list<string>  $existingRemaining
     * @return array{0: list<string>, 1: list<string>, 2: list<string>}
     *
     * @phpstan-impure
     */
    private function classifyRequestedActions(array $requested, string $contents, array $existingRemaining): array
    {
        $actionsToPublish = [];
        $alreadyPublished = [];
        $notInList = [];

        foreach ($requested as $action) {
            if (in_array($action, $existingRemaining, true)) {
                $actionsToPublish[] = $action;

                continue;
            }

            if ($this->isActionCovered($action, $contents)) {
                $alreadyPublished[] = $action;

                continue;
            }

            $notInList[] = $action;
            $actionsToPublish[] = $action;
        }

        return [$actionsToPublish, $alreadyPublished, $notInList];
    }

    /**
     * Whether an action not currently listed in `Route::restifyAuth()`'s actions
     * array has real, on-disk proof it was already published: its dedicated
     * route name, or its controller file.
     */
    private function isActionCovered(string $action, string $contents): bool
    {
        $routeName = self::ROUTE_NAMES[$action] ?? $action;

        if (str_contains($contents, "->name('restify.{$routeName}')")) {
            return true;
        }

        $controller = self::ACTIONS[$action]['controller'] ?? null;

        if ($controller === null) {
            return false;
        }

        $controllerPath = app_path(
            'Http/Controllers/Restify/Auth/'.Str::replaceLast('.stub', '.php', $controller)
        );

        return $this->files->exists($controllerPath);
    }

    /**
     * @return array{offset: int, length: int, existingRemaining: list<string>}
     *
     * @throws RuntimeException
     *
     * @phpstan-impure
     */
    private function parseRestifyAuthCall(string $contents): array
    {
        [$offset, $length, $arguments] = $this->locateRestifyAuthCall($contents);

        return [
            'offset' => $offset,
            'length' => $length,
            'existingRemaining' => $this->parseExistingRemainingActions($arguments),
        ];
    }

    /**
     * Finds the real `Route::restifyAuth(...)` call by walking the file's tokens
     * looking for `Route` (or the fully-qualified `\Route`), `::`, `restifyAuth`,
     * a balanced pair of parentheses, and a trailing `;`. A call is only ever
     * built from that exact token sequence, so text sitting inside a comment, a
     * heredoc/nowdoc, a multi-line string, or inline HTML after a `?>` is never
     * mistaken for it, and a call next to other code on the same line is still
     * found. A fully-qualified `\Route::restifyAuth()` is rewritten to the bare
     * `Route::` facade call; `ensureRouteFacadeImported()` makes sure the facade
     * is actually imported when that happens.
     *
     * @return array{0: int, 1: int, 2: string}
     *
     * @throws RuntimeException
     */
    private function locateRestifyAuthCall(string $contents): array
    {
        $tokens = PhpToken::tokenize($contents);
        $count = count($tokens);

        $match = null;
        $closeTagPos = null;

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if ($token->is(T_CLOSE_TAG)) {
                $closeTagPos = $token->pos;
            }

            $isRouteToken = ($token->is(T_STRING) && $token->text === 'Route')
                || ($token->is(T_NAME_FULLY_QUALIFIED) && $token->text === '\Route');

            if (! $isRouteToken) {
                continue;
            }

            $call = $this->matchRestifyAuthCall($tokens, $count, $i, $contents);

            if ($call === null) {
                continue;
            }

            if ($match !== null) {
                throw new RuntimeException(
                    'Multiple Route::restifyAuth() calls were found in routes/api.php. This command can only merge into a single call. Combine them manually and try again.'
                );
            }

            $match = $call;
        }

        if ($match === null) {
            throw new RuntimeException(
                'No Route::restifyAuth() call was found in routes/api.php. Add Route::restifyAuth(); and try again.'
            );
        }

        if ($closeTagPos !== null && $closeTagPos >= $match['offset'] + $match['length']) {
            throw new RuntimeException(
                'routes/api.php has a closing ?> tag after the Route::restifyAuth() call. Remove it so this command can safely append the generated routes.'
            );
        }

        return [$match['offset'], $match['length'], $match['arguments']];
    }

    /**
     * Attempts to match `::restifyAuth(...);` starting right after the `Route`
     * token at index `$routeIndex`, returning null when the token sequence
     * does not form a real call.
     *
     * @param  array<PhpToken>  $tokens
     * @return array{offset: int, length: int, arguments: string}|null
     */
    private function matchRestifyAuthCall(array $tokens, int $count, int $routeIndex, string $contents): ?array
    {
        $routeToken = $tokens[$routeIndex];

        $cursor = $this->skipInsignificantTokens($tokens, $count, $routeIndex + 1);

        if ($cursor === null || ! $tokens[$cursor]->is(T_DOUBLE_COLON)) {
            return null;
        }

        $cursor = $this->skipInsignificantTokens($tokens, $count, $cursor + 1);

        if ($cursor === null || ! $tokens[$cursor]->is(T_STRING) || $tokens[$cursor]->text !== 'restifyAuth') {
            return null;
        }

        $cursor = $this->skipInsignificantTokens($tokens, $count, $cursor + 1);

        if ($cursor === null || $tokens[$cursor]->text !== '(') {
            return null;
        }

        $argumentsStart = $tokens[$cursor]->pos + 1;
        $depth = 1;
        $closeParenIndex = null;

        for ($j = $cursor + 1; $j < $count; $j++) {
            $text = $tokens[$j]->text;

            if ($text === '(') {
                $depth++;
            } elseif ($text === ')') {
                $depth--;

                if ($depth === 0) {
                    $closeParenIndex = $j;

                    break;
                }
            }
        }

        if ($closeParenIndex === null) {
            return null;
        }

        $semicolonCursor = $this->skipInsignificantTokens($tokens, $count, $closeParenIndex + 1);

        if ($semicolonCursor === null || $tokens[$semicolonCursor]->text !== ';') {
            return null;
        }

        $endPos = $tokens[$semicolonCursor]->pos + 1;
        $argumentsEnd = $tokens[$closeParenIndex]->pos;

        return [
            'offset' => $routeToken->pos,
            'length' => $endPos - $routeToken->pos,
            'arguments' => substr($contents, $argumentsStart, $argumentsEnd - $argumentsStart),
        ];
    }

    /**
     * @param  array<PhpToken>  $tokens
     */
    private function skipInsignificantTokens(array $tokens, int $count, int $from): ?int
    {
        for ($i = $from; $i < $count; $i++) {
            if (! $tokens[$i]->is([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Ensures the routes file imports the Route facade: every stub route this
     * command appends calls `Route::` directly, and a namespaced file with no
     * import would resolve that to a class in its own namespace instead of the
     * facade. Left alone when the import - flat or grouped - is already there.
     * Refused when `Route` already resolves to a different class: rewriting an
     * existing import out from under the developer is not this command's call
     * to make.
     *
     * @throws RuntimeException
     */
    private function ensureRouteFacadeImported(string $contents): string
    {
        $facade = 'Illuminate\Support\Facades\Route';

        [$insertPos, $imports] = $this->findUseImportInsertPosition($contents);

        if (array_key_exists('Route', $imports)) {
            if ($imports['Route'] === $facade) {
                return $contents;
            }

            throw new RuntimeException(
                "routes/api.php already imports [{$imports['Route']}] as Route, which conflicts with the [{$facade}] facade the published routes need. Rename that import and try again."
            );
        }

        $insertion = "use {$facade};\n";

        if ($insertPos > 0 && $contents[$insertPos - 1] !== "\n") {
            $insertion = "\n".$insertion;
        }

        return substr_replace($contents, $insertion, $insertPos, 0);
    }

    /**
     * Walks the file's tokens to find where a new top-level `use` import
     * statement belongs (right after the last existing one, or else after
     * `namespace`, or else right after `<?php`), and collects every top-level
     * import already declared (`alias => fully-qualified name`) along the way,
     * so a conflicting one can be detected without a second pass.
     *
     * @return array{0: int, 1: array<string, string>}
     */
    private function findUseImportInsertPosition(string $contents): array
    {
        $tokens = PhpToken::tokenize($contents);
        $count = count($tokens);

        $depth = 0;
        $openTagEnd = 0;
        $namespaceEnd = null;
        $lastUseEnd = null;
        $imports = [];

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if ($token->is(T_OPEN_TAG)) {
                $openTagEnd = $token->pos + strlen($token->text);

                continue;
            }

            if ($token->text === '{') {
                $depth++;

                continue;
            }

            if ($token->text === '}') {
                $depth--;

                continue;
            }

            if ($depth !== 0) {
                continue;
            }

            if ($token->is(T_NAMESPACE)) {
                $end = $this->findTopLevelStatementEnd($tokens, $count, $i + 1);

                if ($end !== null) {
                    $namespaceEnd = $tokens[$end]->pos + 1;
                }

                continue;
            }

            if (! $token->is(T_USE)) {
                continue;
            }

            $end = $this->findTopLevelStatementEnd($tokens, $count, $i + 1);

            if ($end === null) {
                continue;
            }

            $imports += $this->parseUseImports(array_slice($tokens, $i + 1, $end - $i - 1));

            $lastUseEnd = $tokens[$end]->pos + 1;
        }

        return [$lastUseEnd ?? $namespaceEnd ?? $openTagEnd, $imports];
    }

    /**
     * Finds the `;` that ends a top-level statement starting at `$from`,
     * tracking `{`/`}` locally so a grouped `use` statement's braces
     * (`use Foo\{Bar, Baz};`) are not mistaken for the end of the statement.
     *
     * @param  array<PhpToken>  $tokens
     */
    private function findTopLevelStatementEnd(array $tokens, int $count, int $from): ?int
    {
        $depth = 0;

        for ($i = $from; $i < $count; $i++) {
            $text = $tokens[$i]->text;

            if ($text === '{') {
                $depth++;
            } elseif ($text === '}') {
                $depth--;
            } elseif ($text === ';' && $depth === 0) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Parses one `use` import statement's tokens - already sliced to exclude
     * the leading `use` keyword and the trailing `;` - into `alias =>
     * fully-qualified name` pairs, resolving both the flat form (`Foo\Bar`,
     * `Foo\Bar as Baz`) and the grouped form (`Foo\{Bar, Baz as Qux}`), and a
     * leading `\` on the flat form or the group prefix (`\Foo\Bar`,
     * `\Foo\{Bar}`), which the tokenizer keeps as part of the name.
     * `use function`/`use const` imports are ignored: neither can ever alias
     * to the `Route` class this command cares about.
     *
     * @param  array<PhpToken>  $tokens
     * @return array<string, string>
     */
    private function parseUseImports(array $tokens): array
    {
        $tokens = array_values(array_filter(
            $tokens,
            fn (PhpToken $token): bool => ! $token->is([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])
        ));

        if (($tokens[0] ?? null)?->is([T_FUNCTION, T_CONST]) === true) {
            return [];
        }

        $index = 0;

        return $this->parseUseImportItems($tokens, $index, count($tokens), '');
    }

    /**
     * @param  array<PhpToken>  $tokens
     * @return array<string, string>
     */
    private function parseUseImportItems(array $tokens, int &$index, int $count, string $groupPrefix): array
    {
        $imports = [];

        while ($index < $count) {
            $name = $groupPrefix;

            while ($index < $count && $tokens[$index]->is([T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NS_SEPARATOR])) {
                $name .= $tokens[$index]->text;
                $index++;
            }

            // A leading `\` is only legal on the first name of a `use` clause
            // (`use \Foo\Bar;`, `use \Foo\{Bar};`), never on a member inside a
            // group, so it is only ever stripped here, where $groupPrefix is
            // still empty.
            if ($groupPrefix === '' && str_starts_with($name, '\\')) {
                $name = substr($name, 1);
            }

            if ($index < $count && $tokens[$index]->text === '{') {
                $index++;

                $imports += $this->parseUseImportItems($tokens, $index, $count, $name);

                if ($index < $count && $tokens[$index]->text === '}') {
                    $index++;
                }
            } elseif ($name !== '') {
                $alias = Str::afterLast($name, '\\');

                if ($index < $count && $tokens[$index]->is(T_AS)) {
                    $index++;

                    if ($index < $count && $tokens[$index]->is(T_STRING)) {
                        $alias = $tokens[$index]->text;
                        $index++;
                    }
                }

                $imports[$alias] = $name;
            }

            if ($index < $count && $tokens[$index]->text === ',') {
                $index++;

                continue;
            }

            break;
        }

        return $imports;
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
            PhpToken::tokenize('<?php '.trim($array).';'),
            fn (PhpToken $token): bool => ! $token->is([T_WHITESPACE, T_OPEN_TAG])
        ));

        if (($tokens[0]->text ?? null) !== '[') {
            throw new RuntimeException($parseError);
        }

        $actions = [];
        $expectingValue = true;
        $closed = false;
        $count = count($tokens);
        $i = 1;

        for (; $i < $count; $i++) {
            $token = $tokens[$i];

            if ($token->text === ']') {
                $closed = true;
                $i++;

                break;
            }

            if ($expectingValue) {
                if (! $token->is(T_CONSTANT_ENCAPSED_STRING)) {
                    throw new RuntimeException($parseError);
                }

                $action = substr($token->text, 1, -1);

                if (str_contains($action, '\\')) {
                    throw new RuntimeException($parseError);
                }

                $actions[] = $action;
                $expectingValue = false;

                continue;
            }

            if ($token->text !== ',') {
                throw new RuntimeException($parseError);
            }

            $expectingValue = true;
        }

        if (! $closed) {
            throw new RuntimeException($parseError);
        }

        for (; $i < $count; $i++) {
            if ($tokens[$i]->text !== ';') {
                throw new RuntimeException($parseError);
            }
        }

        return $actions;
    }

    /**
     * @param  list<string>  $actions
     *
     * @phpstan-impure
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

        $this->wroteAnyFile = true;
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
     *
     * @phpstan-impure
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
