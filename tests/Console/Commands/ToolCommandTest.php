<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithGeneratedApp;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class ToolCommandTest extends IntegrationTestCase
{
    use InteractsWithGeneratedApp;

    #[Test]
    public function it_generates_an_mcp_tool_class(): void
    {
        $this->artisan('restify:mcp-tool', ['name' => 'SendEmail'])
            ->expectsOutputToContain('created successfully')
            ->assertExitCode(0);

        $path = $this->generatedAppPath.'/Restify/Mcp/Tools/SendEmailTool.php';
        $this->assertFileExists($path);

        $content = File::get($path);
        $this->assertStringContainsString('namespace App\Restify\Mcp\Tools;', $content);
        $this->assertStringContainsString('class SendEmailTool extends Tool', $content);
        $this->assertStringContainsString("return 'send-email';", $content);
    }

    #[Test]
    public function it_does_not_double_append_the_tool_suffix(): void
    {
        $this->artisan('restify:mcp-tool', ['name' => 'SendEmailTool'])
            ->assertExitCode(0);

        $path = $this->generatedAppPath.'/Restify/Mcp/Tools/SendEmailTool.php';
        $this->assertFileExists($path);
        $this->assertStringContainsString("return 'send-email';", File::get($path));
    }

    #[Test]
    public function it_refuses_to_overwrite_an_existing_tool_without_force(): void
    {
        $path = $this->generatedAppPath.'/Restify/Mcp/Tools/SendEmailTool.php';
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'original content');

        $this->artisan('restify:mcp-tool', ['name' => 'SendEmail'])
            ->expectsOutputToContain('Tool already exists.')
            ->assertExitCode(0);

        $this->assertSame('original content', File::get($path));
    }

    #[Test]
    public function it_overwrites_an_existing_tool_with_force(): void
    {
        $path = $this->generatedAppPath.'/Restify/Mcp/Tools/SendEmailTool.php';
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'original content');

        $this->artisan('restify:mcp-tool', ['name' => 'SendEmail', '--force' => true])
            ->expectsOutputToContain('created successfully')
            ->assertExitCode(0);

        $this->assertStringContainsString('class SendEmailTool extends Tool', File::get($path));
    }

    #[Test]
    public function it_rejects_a_reserved_php_name(): void
    {
        $this->artisan('restify:mcp-tool', ['name' => 'class'])
            ->expectsOutputToContain('reserved by PHP')
            ->assertExitCode(0);

        $this->assertDirectoryDoesNotExist($this->generatedAppPath.'/Restify/Mcp/Tools');
    }
}
