<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class PublishAuthCommandTest extends IntegrationTestCase
{
    private Filesystem $files;

    private string $apiRoutesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->apiRoutesPath = base_path('routes/api.php');

        $this->files->ensureDirectoryExists(dirname($this->apiRoutesPath));
        $this->files->put($this->apiRoutesPath, "<?php\n\nRoute::restifyAuth();\n");
    }

    protected function tearDown(): void
    {
        $this->files->delete($this->apiRoutesPath);
        $this->files->deleteDirectory(app_path('Http/Controllers/Restify'));
        $this->files->deleteDirectory(app_path('Notifications/Restify'));

        parent::tearDown();
    }

    #[Test]
    public function reset_password_publishes_its_own_route_and_controller_not_a_duplicate_forgot_password_route(): void
    {
        $this->artisan('restify:auth', ['--actions' => 'forgotPassword,resetPassword'])->run();

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
    #[TestWith(['verifyEmail', "Route::post('verify/{id}/{hash}', \\App\\Http\\Controllers\\Restify\\Auth\\VerifyController::class)", null], 'verifyEmail')]
    public function each_action_publishes_only_its_own_route(string $action, string $expectedRouteSnippet, ?string $expectedController): void
    {
        $this->artisan('restify:auth', ['--actions' => $action])->run();

        $routes = $this->files->get($this->apiRoutesPath);

        $this->assertStringContainsString($expectedRouteSnippet, $routes);

        foreach (['LoginController', 'RegisterController', 'ForgotPasswordController', 'ResetPasswordController'] as $controller) {
            $path = app_path("Http/Controllers/Restify/Auth/{$controller}.php");

            if ($controller === $expectedController) {
                $this->assertFileExists($path);
            } else {
                $this->assertFileDoesNotExist($path);
            }
        }
    }
}
