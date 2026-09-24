<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class PublishAuthCommandTest extends IntegrationTestCase
{
    /**
     * The macro call left behind after publishing only `login` from a fresh
     * `Route::restifyAuth();` file (default `AUTH_ACTIONS` minus `login`).
     */
    private const REMAINING_AFTER_LOGIN = "Route::restifyAuth(actions: ['register', 'logout', 'verifyEmail', 'forgotPassword', 'resetPassword']);";

    private Filesystem $files;

    private string $tempBasePath;

    private string $apiRoutesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->tempBasePath = sys_get_temp_dir().'/restify-publish-auth-'.uniqid('', true);

        $this->files->ensureDirectoryExists($this->tempBasePath.'/app');
        $this->files->ensureDirectoryExists($this->tempBasePath.'/routes');

        $this->files->put($this->tempBasePath.'/composer.json', json_encode([
            'autoload' => ['psr-4' => ['App\\' => 'app/']],
        ]));

        $this->apiRoutesPath = $this->tempBasePath.'/routes/api.php';
        $this->files->put($this->apiRoutesPath, "<?php\n\nRoute::restifyAuth();\n");

        $this->app->setBasePath($this->tempBasePath);
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->tempBasePath);

        parent::tearDown();
    }

    #[Test]
    public function it_fails_with_a_clear_error_when_routes_api_is_missing(): void
    {
        $this->files->delete($this->apiRoutesPath);

        $this->artisan('restify:auth')
            ->expectsOutputToContain('install:api')
            ->assertExitCode(1)
            ->run();

        $this->assertFileDoesNotExist(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
    }

    #[Test]
    #[TestWith(['loginn', 'Unknown action [loginn].'], 'an unknown action')]
    #[TestWith(['logout', "The 'logout' action is registered by the macro; there is nothing to publish for it."], "'logout' has no stub")]
    #[TestWith([',', 'No valid actions were found in --actions.'], 'only a comma')]
    public function it_fails_before_touching_any_file_for_an_unpublishable_action(string $actionsOption, string $expectedMessage): void
    {
        $this->artisan('restify:auth', ['--actions' => $actionsOption])
            ->expectsOutputToContain($expectedMessage)
            ->assertExitCode(1)
            ->run();

        $this->assertFileDoesNotExist(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
        $this->assertSame("<?php\n\nRoute::restifyAuth();\n", $this->files->get($this->apiRoutesPath));
    }

    #[Test]
    public function it_refuses_to_merge_routes_when_the_macro_call_has_a_prefix(): void
    {
        $this->files->put($this->apiRoutesPath, "<?php\n\nRoute::restifyAuth('/auth');\n");

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(1)
            ->run();

        $this->assertFileDoesNotExist(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
    }

    #[Test]
    public function it_fails_when_no_restify_auth_macro_call_exists(): void
    {
        $this->files->put($this->apiRoutesPath, "<?php\n\n// no restifyAuth() call here\n");

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(1)
            ->run();

        $this->assertFileDoesNotExist(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
    }

    #[Test]
    public function running_the_command_twice_does_not_duplicate_routes(): void
    {
        $this->artisan('restify:auth', ['--actions' => 'login'])->assertExitCode(0)->run();
        $this->artisan('restify:auth', ['--actions' => 'login'])->assertExitCode(0)->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertSame(1, substr_count($routes, "Route::post('login'"));
        $this->assertStringContainsString(
            "Route::restifyAuth(actions: ['register', 'logout', 'verifyEmail', 'forgotPassword', 'resetPassword']);",
            $routes
        );
    }

    #[Test]
    public function running_the_command_twice_prints_nothing_left_to_publish_and_does_not_touch_the_customised_files(): void
    {
        $this->artisan('restify:auth', ['--actions' => 'login,forgotPassword'])->assertExitCode(0)->run();

        $controllerPath = app_path('Http/Controllers/Restify/Auth/LoginController.php');
        $notificationPath = app_path('Notifications/Restify/ForgotPasswordNotification.php');

        $this->files->put($controllerPath, "<?php\n\n// customised login controller\n");
        $this->files->put($notificationPath, "<?php\n\n// customised forgot password notification\n");

        $routesBeforeSecondRun = $this->files->get($this->apiRoutesPath);

        $this->artisan('restify:auth', ['--actions' => 'login,forgotPassword'])
            ->expectsOutputToContain('Nothing left to publish')
            ->assertExitCode(0)
            ->run();

        $this->assertStringContainsString('customised login controller', $this->files->get($controllerPath));
        $this->assertStringContainsString('customised forgot password notification', $this->files->get($notificationPath));
        $this->assertSame($routesBeforeSecondRun, $this->files->get($this->apiRoutesPath));
    }

    #[Test]
    public function publish_stub_skips_a_file_that_already_exists_on_disk_and_warns(): void
    {
        $controllerPath = app_path('Http/Controllers/Restify/Auth/LoginController.php');
        $this->files->ensureDirectoryExists(dirname($controllerPath));
        $this->files->put($controllerPath, "<?php\n\n// hand-written before publishing\n");

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->expectsOutputToContain('LoginController.php already exists, skipped')
            ->assertExitCode(0)
            ->run();

        $this->assertStringContainsString('hand-written before publishing', $this->files->get($controllerPath));
    }

    #[Test]
    public function reset_password_publishes_its_own_route_and_controller_not_a_duplicate_forgot_password_route(): void
    {
        $this->artisan('restify:auth', ['--actions' => 'forgotPassword,resetPassword'])
            ->assertExitCode(0)
            ->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertSame(
            1,
            substr_count($routes, "Route::post('forgotPassword', \\App\\Http\\Controllers\\Restify\\Auth\\ForgotPasswordController::class)")
        );
        $this->assertSame(
            1,
            substr_count($routes, "Route::post('resetPassword', \\App\\Http\\Controllers\\Restify\\Auth\\ResetPasswordController::class)")
        );
        $this->assertFileExists(app_path('Http/Controllers/Restify/Auth/ForgotPasswordController.php'));
        $this->assertFileExists(app_path('Http/Controllers/Restify/Auth/ResetPasswordController.php'));
    }

    #[Test]
    #[TestWith(['login', "Route::post('login', \\App\\Http\\Controllers\\Restify\\Auth\\LoginController::class)", 'LoginController'], 'login')]
    #[TestWith(['register', "Route::post('register', \\App\\Http\\Controllers\\Restify\\Auth\\RegisterController::class)", 'RegisterController'], 'register')]
    #[TestWith(['forgotPassword', "Route::post('forgotPassword', \\App\\Http\\Controllers\\Restify\\Auth\\ForgotPasswordController::class)", 'ForgotPasswordController'], 'forgotPassword')]
    #[TestWith(['resetPassword', "Route::post('resetPassword', \\App\\Http\\Controllers\\Restify\\Auth\\ResetPasswordController::class)", 'ResetPasswordController'], 'resetPassword')]
    #[TestWith(['verifyEmail', "Route::post('verify/{id}/{hash}', \\App\\Http\\Controllers\\Restify\\Auth\\VerifyController::class)", 'VerifyController'], 'verifyEmail')]
    public function each_action_publishes_only_its_own_route(string $action, string $expectedRouteSnippet, string $expectedController): void
    {
        $this->artisan('restify:auth', ['--actions' => $action])
            ->assertExitCode(0)
            ->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertStringContainsString($expectedRouteSnippet, $routes);

        foreach (['LoginController', 'RegisterController', 'ForgotPasswordController', 'ResetPasswordController', 'VerifyController'] as $controller) {
            $path = app_path("Http/Controllers/Restify/Auth/{$controller}.php");

            if ($controller === $expectedController) {
                $this->assertFileExists($path);
            } else {
                $this->assertFileDoesNotExist($path);
            }
        }
    }

    #[Test]
    public function it_does_not_publish_the_forgot_password_notification_when_not_requested(): void
    {
        $this->artisan('restify:auth', ['--actions' => 'login'])->run();

        $this->assertFileDoesNotExist(app_path('Notifications/Restify/ForgotPasswordNotification.php'));
    }

    #[Test]
    public function it_publishes_the_forgot_password_notification_when_requested(): void
    {
        $this->artisan('restify:auth', ['--actions' => 'forgotPassword'])->run();

        $this->assertFileExists(app_path('Notifications/Restify/ForgotPasswordNotification.php'));
    }

    #[Test]
    public function it_rewrites_the_macro_call_with_the_actions_left_unpublished(): void
    {
        $this->artisan('restify:auth', ['--actions' => 'login,register'])->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertStringContainsString(
            "Route::restifyAuth(actions: ['logout', 'verifyEmail', 'forgotPassword', 'resetPassword']);",
            $routes
        );
    }

    #[Test]
    public function publishing_with_no_actions_option_leaves_only_logout_for_the_macro(): void
    {
        $this->artisan('restify:auth')->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertStringContainsString("Route::restifyAuth(actions: ['logout']);", $routes);
        $this->assertSame(1, substr_count($routes, "Route::post('login'"));
    }

    #[Test]
    public function the_verify_alias_does_not_duplicate_the_verify_route(): void
    {
        $this->artisan('restify:auth', ['--actions' => 'verify'])->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertSame(1, substr_count($routes, "Route::post('verify/{id}/{hash}'"));
        $this->assertFileExists(app_path('Http/Controllers/Restify/Auth/VerifyController.php'));
        $this->assertStringContainsString(
            "Route::restifyAuth(actions: ['register', 'login', 'logout', 'forgotPassword', 'resetPassword']);",
            $routes
        );
    }

    #[Test]
    #[TestWith(['login, register'], 'a space after the comma')]
    #[TestWith(['Login,Register'], 'PascalCase')]
    public function actions_are_trimmed_and_canonicalized(string $actionsOption): void
    {
        $this->artisan('restify:auth', ['--actions' => $actionsOption])->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertSame(1, substr_count($routes, "Route::post('login'"));
        $this->assertSame(1, substr_count($routes, "Route::post('register'"));
    }

    #[Test]
    #[TestWith(["Route::restifyAuth(actions: ['login', 'register']);"], 'single quotes')]
    #[TestWith(["Route::restifyAuth(actions: ['login', 'register',]);"], 'a trailing comma')]
    #[TestWith(["Route::restifyAuth(actions: [\n    'login',\n    'register',\n]);"], 'multi-line')]
    #[TestWith(['Route::restifyAuth(actions: ["login", "register"]);'], 'double quotes with spaces (JSON stays supported)')]
    public function it_parses_an_existing_actions_list_written_as_a_php_array(string $macroCall): void
    {
        $this->files->put($this->apiRoutesPath, "<?php\n\n{$macroCall}\n");

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(0)
            ->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertStringContainsString("Route::post('login'", $routes);
        $this->assertStringNotContainsString("Route::post('register'", $routes);
        $this->assertStringContainsString("Route::restifyAuth(actions: ['register']);", $routes);
    }

    #[Test]
    public function it_refuses_to_merge_routes_when_the_actions_list_contains_non_string_values(): void
    {
        $this->files->put($this->apiRoutesPath, "<?php\n\nRoute::restifyAuth(actions: [1, 2]);\n");

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(1)
            ->run();

        $this->assertFileDoesNotExist(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
    }

    #[Test]
    public function publishing_the_last_remaining_action_writes_an_explicit_empty_actions_array(): void
    {
        $this->files->put($this->apiRoutesPath, "<?php\n\nRoute::restifyAuth(actions: ['login']);\n");

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(0)
            ->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertStringContainsString('Route::restifyAuth(actions: []);', $routes);
    }

    #[Test]
    public function a_commented_out_restify_auth_call_is_not_treated_as_the_real_one(): void
    {
        $this->files->put($this->apiRoutesPath, "<?php\n\n// Route::restifyAuth();\n");

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(1)
            ->run();

        $this->assertFileDoesNotExist(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
    }

    #[Test]
    public function it_rewrites_the_real_call_and_leaves_an_identical_looking_comment_above_it_untouched(): void
    {
        $this->files->put(
            $this->apiRoutesPath,
            "<?php\n\n// Route::restifyAuth();\nRoute::restifyAuth();\n"
        );

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(0)
            ->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertStringContainsString("// Route::restifyAuth();\n", $routes);
        $this->assertSame(1, substr_count($routes, "Route::post('login'"));
        $this->assertStringContainsString(
            "Route::restifyAuth(actions: ['register', 'logout', 'verifyEmail', 'forgotPassword', 'resetPassword']);",
            $routes
        );
    }

    #[Test]
    public function a_restify_auth_call_written_inside_a_block_comment_is_not_treated_as_the_real_one(): void
    {
        $this->files->put(
            $this->apiRoutesPath,
            "<?php\n\n/*\nRoute::restifyAuth();\n*/\n"
        );

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(1)
            ->run();

        $this->assertFileDoesNotExist(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
    }

    #[Test]
    public function it_refuses_to_merge_routes_when_two_real_restify_auth_calls_exist(): void
    {
        $this->files->put(
            $this->apiRoutesPath,
            "<?php\n\nRoute::restifyAuth(actions: ['login']);\nRoute::restifyAuth(actions: ['register']);\n"
        );

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(1)
            ->run();

        $this->assertFileDoesNotExist(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
        $this->assertSame(
            "<?php\n\nRoute::restifyAuth(actions: ['login']);\nRoute::restifyAuth(actions: ['register']);\n",
            $this->files->get($this->apiRoutesPath)
        );
    }

    #[Test]
    public function it_accepts_a_trailing_comma_after_the_actions_argument_itself(): void
    {
        $this->files->put(
            $this->apiRoutesPath,
            "<?php\n\nRoute::restifyAuth(\n    actions: ['login', 'register'],\n);\n"
        );

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(0)
            ->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertStringContainsString("Route::post('login'", $routes);
        $this->assertStringNotContainsString("Route::post('register'", $routes);
        $this->assertStringContainsString("Route::restifyAuth(actions: ['register']);", $routes);
    }

    #[Test]
    public function a_leading_backslash_on_the_macro_call_in_a_non_namespaced_file_is_rewritten_to_the_facade_with_the_import_added(): void
    {
        $this->files->put($this->apiRoutesPath, "<?php\n\n\\Route::restifyAuth();\n");

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(0)
            ->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertSame(
            "<?php\nuse Illuminate\\Support\\Facades\\Route;\n\n".self::REMAINING_AFTER_LOGIN."\n\n",
            Str::before($routes, "Route::post('login'")
        );
        $this->assertStringNotContainsString('\\Route::restifyAuth(', $routes);
        $this->assertFileExists(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
        $this->assertPhpFileParses($this->apiRoutesPath);
    }

    #[Test]
    public function a_leading_backslash_on_the_macro_call_in_a_namespaced_file_is_rewritten_and_the_import_is_added_after_the_namespace(): void
    {
        $this->files->put(
            $this->apiRoutesPath,
            "<?php\n\nnamespace App\\Routes;\n\n\\Route::restifyAuth();\n"
        );

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(0)
            ->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertSame(
            "<?php\n\nnamespace App\\Routes;\nuse Illuminate\\Support\\Facades\\Route;\n\n\n".self::REMAINING_AFTER_LOGIN."\n\n",
            Str::before($routes, "Route::post('login'")
        );
        $this->assertStringNotContainsString('\\Route::restifyAuth(', $routes);
        $this->assertFileExists(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
        $this->assertPhpFileParses($this->apiRoutesPath);
    }

    #[Test]
    public function a_bare_route_call_with_no_existing_import_gets_the_facade_import(): void
    {
        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(0)
            ->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertSame(
            "<?php\nuse Illuminate\\Support\\Facades\\Route;\n\n".self::REMAINING_AFTER_LOGIN."\n\n",
            Str::before($routes, "Route::post('login'")
        );
        $this->assertFileExists(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
        $this->assertPhpFileParses($this->apiRoutesPath);
    }

    #[Test]
    public function an_existing_route_facade_import_is_not_duplicated(): void
    {
        $this->files->put(
            $this->apiRoutesPath,
            "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::restifyAuth();\n"
        );

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(0)
            ->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertSame(1, substr_count($routes, 'use Illuminate\\Support\\Facades\\Route;'));
        $this->assertSame(
            "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n".self::REMAINING_AFTER_LOGIN."\n\n",
            Str::before($routes, "Route::post('login'")
        );
        $this->assertFileExists(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
        $this->assertPhpFileParses($this->apiRoutesPath);
    }

    #[Test]
    public function a_grouped_route_facade_import_is_recognised_and_not_duplicated(): void
    {
        $this->files->put(
            $this->apiRoutesPath,
            "<?php\n\nuse Illuminate\\Support\\Facades\\{Auth, Route};\n\nRoute::restifyAuth();\n"
        );

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(0)
            ->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertSame(0, substr_count($routes, 'use Illuminate\\Support\\Facades\\Route;'));
        $this->assertSame(
            "<?php\n\nuse Illuminate\\Support\\Facades\\{Auth, Route};\n\n".self::REMAINING_AFTER_LOGIN."\n\n",
            Str::before($routes, "Route::post('login'")
        );
        $this->assertFileExists(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
        $this->assertPhpFileParses($this->apiRoutesPath);
    }

    #[Test]
    #[TestWith(['use App\\Providers\\RouteServiceProvider as Route;'], 'aliased import')]
    #[TestWith(['use App\\Providers\\Route;'], 'a class literally named Route')]
    public function a_conflicting_route_import_is_refused_and_the_file_is_left_unchanged(string $conflictingImport): void
    {
        $original = "<?php\n\n{$conflictingImport}\n\nRoute::restifyAuth();\n";
        $this->files->put($this->apiRoutesPath, $original);

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->expectsOutputToContain('already imports')
            ->assertExitCode(1)
            ->run();

        $this->assertFileDoesNotExist(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
        $this->assertSame($original, $this->files->get($this->apiRoutesPath));
    }

    #[Test]
    public function a_leading_backslash_on_an_existing_flat_route_facade_import_is_recognised_and_not_duplicated(): void
    {
        $this->files->put(
            $this->apiRoutesPath,
            "<?php\n\nuse \\Illuminate\\Support\\Facades\\Route;\n\nRoute::restifyAuth();\n"
        );

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(0)
            ->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertSame(1, substr_count($routes, 'use \\Illuminate\\Support\\Facades\\Route;'));
        $this->assertSame(
            "<?php\n\nuse \\Illuminate\\Support\\Facades\\Route;\n\n".self::REMAINING_AFTER_LOGIN."\n\n",
            Str::before($routes, "Route::post('login'")
        );
        $this->assertFileExists(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
        $this->assertPhpFileParses($this->apiRoutesPath);
    }

    #[Test]
    public function a_leading_backslash_on_an_existing_grouped_route_facade_import_is_recognised_and_not_duplicated(): void
    {
        $this->files->put(
            $this->apiRoutesPath,
            "<?php\n\nuse \\Illuminate\\Support\\Facades\\{Auth, Route};\n\nRoute::restifyAuth();\n"
        );

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(0)
            ->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertSame(0, substr_count($routes, 'use Illuminate\\Support\\Facades\\Route;'));
        $this->assertSame(
            "<?php\n\nuse \\Illuminate\\Support\\Facades\\{Auth, Route};\n\n".self::REMAINING_AFTER_LOGIN."\n\n",
            Str::before($routes, "Route::post('login'")
        );
        $this->assertFileExists(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
        $this->assertPhpFileParses($this->apiRoutesPath);
    }

    #[Test]
    public function a_leading_backslash_on_a_conflicting_route_import_is_refused_and_the_file_is_left_unchanged(): void
    {
        $original = "<?php\n\nuse \\Foo\\Route;\n\nRoute::restifyAuth();\n";
        $this->files->put($this->apiRoutesPath, $original);

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->expectsOutputToContain('already imports')
            ->assertExitCode(1)
            ->run();

        $this->assertFileDoesNotExist(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
        $this->assertSame($original, $this->files->get($this->apiRoutesPath));
    }

    #[Test]
    public function re_requesting_an_already_published_action_warns_and_restores_a_deleted_controller(): void
    {
        $this->artisan('restify:auth', ['--actions' => 'login'])->assertExitCode(0)->run();

        $controllerPath = app_path('Http/Controllers/Restify/Auth/LoginController.php');

        $this->files->delete($controllerPath);
        $this->assertFileDoesNotExist($controllerPath);

        $routesBeforeSecondRun = $this->files->get($this->apiRoutesPath);

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->expectsOutputToContain('login is already published, skipped.')
            ->assertExitCode(0)
            ->run();

        $this->assertFileExists($controllerPath);
        $this->assertSame($routesBeforeSecondRun, $this->files->get($this->apiRoutesPath));
    }

    #[Test]
    #[TestWith(["\$fake = <<<EOT\nRoute::restifyAuth();\nEOT;"], 'a heredoc')]
    #[TestWith(["\$fake = <<<'EOT'\nRoute::restifyAuth();\nEOT;"], 'a nowdoc')]
    #[TestWith(["\$fake = \"line one\nRoute::restifyAuth();\nline three\";"], 'a multi-line string')]
    public function text_that_only_looks_like_the_call_inside_a_non_code_context_is_not_treated_as_the_real_one(string $fakeBlock): void
    {
        $original = "<?php\n\n{$fakeBlock}\n";
        $this->files->put($this->apiRoutesPath, $original);

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->expectsOutputToContain('No Route::restifyAuth() call was found in routes/api.php.')
            ->assertExitCode(1)
            ->run();

        $this->assertFileDoesNotExist(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
        $this->assertSame($original, $this->files->get($this->apiRoutesPath));
    }

    #[Test]
    #[TestWith(["\$fake = <<<EOT\nRoute::restifyAuth();\nEOT;"], 'a heredoc')]
    #[TestWith(["\$fake = <<<'EOT'\nRoute::restifyAuth();\nEOT;"], 'a nowdoc')]
    #[TestWith(["\$fake = \"line one\nRoute::restifyAuth();\nline three\";"], 'a multi-line string')]
    public function a_real_call_next_to_text_that_only_looks_like_one_is_found_and_the_fake_text_is_left_untouched(string $fakeBlock): void
    {
        $this->files->put($this->apiRoutesPath, "<?php\n\n{$fakeBlock}\n\nRoute::restifyAuth();\n");

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(0)
            ->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertStringContainsString($fakeBlock, $routes);
        $this->assertSame(1, substr_count($routes, "Route::post('login'"));
        $this->assertStringContainsString(self::REMAINING_AFTER_LOGIN, $routes);
        $this->assertFileExists(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
        $this->assertPhpFileParses($this->apiRoutesPath);
    }

    #[Test]
    public function a_call_on_the_same_line_as_other_code_inside_a_closure_is_found(): void
    {
        $this->files->put(
            $this->apiRoutesPath,
            "<?php\n\nRoute::group([], function () { Route::restifyAuth(); });\n"
        );

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(0)
            ->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertStringContainsString(
            'Route::group([], function () { '.self::REMAINING_AFTER_LOGIN.' });',
            $routes
        );
        $this->assertFileExists(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
        $this->assertPhpFileParses($this->apiRoutesPath);
    }

    #[Test]
    public function restifyauth_text_written_as_inline_html_after_a_closing_tag_is_not_treated_as_the_real_call(): void
    {
        $original = "<?php\n?>\nRoute::restifyAuth();\n";
        $this->files->put($this->apiRoutesPath, $original);

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->expectsOutputToContain('No Route::restifyAuth() call was found in routes/api.php.')
            ->assertExitCode(1)
            ->run();

        $this->assertFileDoesNotExist(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
        $this->assertSame($original, $this->files->get($this->apiRoutesPath));
    }

    #[Test]
    public function a_real_call_after_an_earlier_closing_tag_is_still_found_and_the_html_is_left_untouched(): void
    {
        $htmlBlock = "?>\nRoute::restifyAuth();\n<?php";

        $this->files->put($this->apiRoutesPath, "<?php\n{$htmlBlock}\n\nRoute::restifyAuth();\n");

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(0)
            ->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertStringContainsString($htmlBlock, $routes);
        $this->assertSame(1, substr_count($routes, "Route::post('login'"));
        $this->assertStringContainsString(self::REMAINING_AFTER_LOGIN, $routes);
        $this->assertFileExists(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
        $this->assertPhpFileParses($this->apiRoutesPath);
    }

    #[Test]
    public function a_closing_tag_after_the_real_call_is_refused_instead_of_appending_after_it(): void
    {
        $original = "<?php\n\nRoute::restifyAuth();\n?>\n";
        $this->files->put($this->apiRoutesPath, $original);

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->expectsOutputToContain('routes/api.php has a closing ?> tag after the Route::restifyAuth() call.')
            ->assertExitCode(1)
            ->run();

        $this->assertFileDoesNotExist(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
        $this->assertSame($original, $this->files->get($this->apiRoutesPath));
    }

    #[Test]
    public function a_multibyte_string_before_the_call_does_not_shift_where_the_call_is_rewritten(): void
    {
        $this->files->put(
            $this->apiRoutesPath,
            "<?php\n\n\$greeting = 'héllo wörld 日本語';\n\nRoute::restifyAuth();\n"
        );

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(0)
            ->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertStringContainsString("\$greeting = 'héllo wörld 日本語';", $routes);
        $this->assertSame(1, substr_count($routes, "Route::post('login'"));
        $this->assertStringContainsString(self::REMAINING_AFTER_LOGIN, $routes);
        $this->assertFileExists(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
        $this->assertPhpFileParses($this->apiRoutesPath);
    }

    #[Test]
    public function a_crlf_routes_file_is_rewritten_correctly(): void
    {
        $this->files->put($this->apiRoutesPath, "<?php\r\n\r\nRoute::restifyAuth();\r\n");

        $this->artisan('restify:auth', ['--actions' => 'login'])
            ->assertExitCode(0)
            ->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertSame(1, substr_count($routes, "Route::post('login'"));
        $this->assertStringContainsString(self::REMAINING_AFTER_LOGIN, $routes);
        $this->assertFileExists(app_path('Http/Controllers/Restify/Auth/LoginController.php'));
        $this->assertPhpFileParses($this->apiRoutesPath);
    }

    #[Test]
    public function requesting_an_action_missing_from_the_actions_list_publishes_it_instead_of_treating_it_as_already_done(): void
    {
        $this->files->put(
            $this->apiRoutesPath,
            "<?php\n\nRoute::restifyAuth(actions: ['login', 'register', 'logout']);\n"
        );

        $this->artisan('restify:auth', ['--actions' => 'resetPassword'])
            ->expectsOutputToContain('resetPassword is not in the Route::restifyAuth() actions list; publishing it now.')
            ->doesntExpectOutputToContain('resetPassword is already published, skipped.')
            ->expectsOutputToContain('Auth controllers published.')
            ->assertExitCode(0)
            ->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertStringContainsString(
            "Route::post('resetPassword', \\App\\Http\\Controllers\\Restify\\Auth\\ResetPasswordController::class)",
            $routes
        );
        $this->assertStringContainsString(
            "Route::restifyAuth(actions: ['login', 'register', 'logout']);",
            $routes
        );
        $this->assertFileExists(app_path('Http/Controllers/Restify/Auth/ResetPasswordController.php'));
        $this->assertPhpFileParses($this->apiRoutesPath);
    }

    #[Test]
    #[TestWith([['register', 'logout'], ['login', 'forgotPassword', 'resetPassword', 'verifyEmail']], 'register and logout tracked')]
    #[TestWith([['logout'], ['login', 'register', 'forgotPassword', 'resetPassword', 'verifyEmail']], 'only logout tracked')]
    public function omitting_actions_treats_every_publishable_action_as_requested_and_restores_whats_missing(array $existingRemaining, array $expectedNotInList): void
    {
        $existing = implode(', ', array_map(fn (string $action): string => "'{$action}'", $existingRemaining));
        $this->files->put($this->apiRoutesPath, "<?php\n\nRoute::restifyAuth(actions: [{$existing}]);\n");

        $command = $this->artisan('restify:auth');

        foreach ($expectedNotInList as $action) {
            $command->expectsOutputToContain("{$action} is not in the Route::restifyAuth() actions list; publishing it now.");
        }

        $command->assertExitCode(0)->run();

        $routes = $this->files->get($this->apiRoutesPath);

        foreach (['login', 'register', 'forgotPassword', 'resetPassword'] as $action) {
            $this->assertSame(1, substr_count($routes, "Route::post('{$action}'"), "route for [{$action}]");
        }
        $this->assertSame(1, substr_count($routes, "Route::post('verify/{id}/{hash}'"));

        $this->assertStringContainsString("Route::restifyAuth(actions: ['logout']);", $routes);

        foreach (['LoginController', 'RegisterController', 'ForgotPasswordController', 'ResetPasswordController', 'VerifyController'] as $controller) {
            $this->assertFileExists(app_path("Http/Controllers/Restify/Auth/{$controller}.php"));
        }
        $this->assertFileExists(app_path('Notifications/Restify/ForgotPasswordNotification.php'));
        $this->assertPhpFileParses($this->apiRoutesPath);
    }

    private function assertPhpFileParses(string $path): void
    {
        exec('php -l '.escapeshellarg($path).' 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, "php -l failed for [{$path}]:\n".implode("\n", $output));
    }
}
