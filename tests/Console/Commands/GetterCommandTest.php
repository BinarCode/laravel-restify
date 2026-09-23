<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithGeneratedApp;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class GetterCommandTest extends IntegrationTestCase
{
    use InteractsWithGeneratedApp;

    #[Test]
    public function it_generates_a_getter_class(): void
    {
        $this->artisan('restify:getter', ['name' => 'ExportPosts'])
            ->expectsOutputToContain('created successfully')
            ->assertExitCode(0);

        $path = $this->generatedAppPath.'/Restify/Getters/ExportPostsGetter.php';
        $this->assertFileExists($path);

        $content = File::get($path);
        $this->assertStringContainsString('namespace App\Restify\Getters;', $content);
        $this->assertStringContainsString('class ExportPostsGetter extends Getter', $content);
        $this->assertStringContainsString('public function handle(GetterRequest $request): JsonResponse', $content);
    }

    #[Test]
    public function it_does_not_double_append_the_getter_suffix(): void
    {
        $this->artisan('restify:getter', ['name' => 'ExportPostsGetter'])
            ->assertExitCode(0);

        $this->assertFileExists($this->generatedAppPath.'/Restify/Getters/ExportPostsGetter.php');
    }

    #[Test]
    public function it_refuses_to_overwrite_an_existing_getter_without_force(): void
    {
        $path = $this->generatedAppPath.'/Restify/Getters/ExportPostsGetter.php';
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'original content');

        $this->artisan('restify:getter', ['name' => 'ExportPosts'])
            ->expectsOutputToContain('Getter already exists.')
            ->assertExitCode(0);

        $this->assertSame('original content', File::get($path));
    }

    #[Test]
    public function it_overwrites_an_existing_getter_with_force(): void
    {
        $path = $this->generatedAppPath.'/Restify/Getters/ExportPostsGetter.php';
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'original content');

        $this->artisan('restify:getter', ['name' => 'ExportPosts', '--force' => true])
            ->expectsOutputToContain('created successfully')
            ->assertExitCode(0);

        $this->assertStringContainsString('class ExportPostsGetter extends Getter', File::get($path));
    }

    #[Test]
    public function it_rejects_a_reserved_php_name(): void
    {
        $this->artisan('restify:getter', ['name' => 'class'])
            ->expectsOutputToContain('reserved by PHP')
            ->assertExitCode(0);

        $this->assertDirectoryDoesNotExist($this->generatedAppPath.'/Restify/Getters');
    }
}
