<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithGeneratedApp;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class ActionCommandTest extends IntegrationTestCase
{
    use InteractsWithGeneratedApp;

    #[Test]
    public function it_generates_an_action_class(): void
    {
        $this->artisan('restify:action', ['name' => 'PublishPost'])
            ->expectsOutputToContain('created successfully')
            ->assertExitCode(0);

        $path = $this->generatedAppPath.'/Restify/Actions/PublishPostAction.php';
        $this->assertFileExists($path);

        $content = File::get($path);
        $this->assertStringContainsString('namespace App\Restify\Actions;', $content);
        $this->assertStringContainsString('class PublishPostAction extends Action', $content);
        $this->assertStringContainsString('public function handle(ActionRequest $request, Collection $models): JsonResponse', $content);
    }

    #[Test]
    public function it_does_not_double_append_the_action_suffix(): void
    {
        $this->artisan('restify:action', ['name' => 'PublishPostAction'])
            ->assertExitCode(0);

        $this->assertFileExists($this->generatedAppPath.'/Restify/Actions/PublishPostAction.php');
    }

    #[Test]
    public function it_refuses_to_overwrite_an_existing_action_without_force(): void
    {
        $path = $this->generatedAppPath.'/Restify/Actions/PublishPostAction.php';
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'original content');

        $this->artisan('restify:action', ['name' => 'PublishPost'])
            ->expectsOutputToContain('Action already exists.')
            ->assertExitCode(0);

        $this->assertSame('original content', File::get($path));
    }

    #[Test]
    public function it_overwrites_an_existing_action_with_force(): void
    {
        $path = $this->generatedAppPath.'/Restify/Actions/PublishPostAction.php';
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'original content');

        $this->artisan('restify:action', ['name' => 'PublishPost', '--force' => true])
            ->expectsOutputToContain('created successfully')
            ->assertExitCode(0);

        $this->assertStringContainsString('class PublishPostAction extends Action', File::get($path));
    }

    #[Test]
    public function it_rejects_a_reserved_php_name(): void
    {
        $this->artisan('restify:action', ['name' => 'class'])
            ->expectsOutputToContain('reserved by PHP')
            ->assertExitCode(0);

        $this->assertDirectoryDoesNotExist($this->generatedAppPath.'/Restify/Actions');
    }
}
