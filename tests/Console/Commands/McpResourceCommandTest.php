<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithGeneratedApp;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class McpResourceCommandTest extends IntegrationTestCase
{
    use InteractsWithGeneratedApp;

    #[Test]
    public function it_generates_an_mcp_resource_class(): void
    {
        $this->artisan('restify:mcp-resource', ['name' => 'Changelog'])
            ->expectsOutputToContain('created successfully')
            ->assertExitCode(0);

        $path = $this->generatedAppPath.'/Restify/Mcp/Resources/ChangelogResource.php';
        $this->assertFileExists($path);

        $content = File::get($path);
        $this->assertStringContainsString('namespace App\Restify\Mcp\Resources;', $content);
        $this->assertStringContainsString('class ChangelogResource extends Resource', $content);
        $this->assertStringContainsString('public function read(): string|Content', $content);
    }

    #[Test]
    public function it_does_not_double_append_the_resource_suffix(): void
    {
        $this->artisan('restify:mcp-resource', ['name' => 'ChangelogResource'])
            ->assertExitCode(0);

        $this->assertFileExists($this->generatedAppPath.'/Restify/Mcp/Resources/ChangelogResource.php');
    }

    #[Test]
    public function it_refuses_to_overwrite_an_existing_resource_without_force(): void
    {
        $path = $this->generatedAppPath.'/Restify/Mcp/Resources/ChangelogResource.php';
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'original content');

        $this->artisan('restify:mcp-resource', ['name' => 'Changelog'])
            ->expectsOutputToContain('Resource already exists.')
            ->assertExitCode(0);

        $this->assertSame('original content', File::get($path));
    }

    #[Test]
    public function it_overwrites_an_existing_resource_with_force(): void
    {
        $path = $this->generatedAppPath.'/Restify/Mcp/Resources/ChangelogResource.php';
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'original content');

        $this->artisan('restify:mcp-resource', ['name' => 'Changelog', '--force' => true])
            ->expectsOutputToContain('created successfully')
            ->assertExitCode(0);

        $this->assertStringContainsString('class ChangelogResource extends Resource', File::get($path));
    }

    #[Test]
    public function it_rejects_a_reserved_php_name(): void
    {
        $this->artisan('restify:mcp-resource', ['name' => 'class'])
            ->expectsOutputToContain('reserved by PHP')
            ->assertExitCode(0);

        $this->assertDirectoryDoesNotExist($this->generatedAppPath.'/Restify/Mcp/Resources');
    }
}
