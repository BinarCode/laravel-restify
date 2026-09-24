<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithGeneratedApp;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class BaseRepositoryCommandTest extends IntegrationTestCase
{
    use InteractsWithGeneratedApp;

    #[Test]
    public function it_generates_the_base_repository_class(): void
    {
        $this->artisan('restify:base-repository', ['name' => 'Repository'])
            ->expectsOutputToContain('created successfully')
            ->assertExitCode(0);

        $path = $this->generatedAppPath.'/Restify/Repository.php';
        $this->assertFileExists($path);

        $content = File::get($path);
        $this->assertStringContainsString('namespace App\Restify;', $content);
        $this->assertStringContainsString('abstract class Repository extends RestifyRepository', $content);
        $this->assertStringContainsString('public static function mainQuery(RestifyRequest $request, Builder|Relation $query)', $content);
        $this->assertStringContainsString('public static function indexQuery(RestifyRequest $request, Builder|Relation $query)', $content);
        $this->assertStringContainsString('public static function showQuery(RestifyRequest $request, Builder|Relation $query)', $content);
    }

    #[Test]
    public function it_is_hidden_from_the_artisan_command_list(): void
    {
        $this->artisan('list')
            ->doesntExpectOutputToContain('restify:base-repository');
    }

    #[Test]
    public function running_it_again_leaves_the_existing_file_untouched(): void
    {
        $path = $this->generatedAppPath.'/Restify/Repository.php';
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'original content');

        $this->artisan('restify:base-repository', ['name' => 'Repository'])
            ->assertExitCode(0);

        $this->assertSame('original content', File::get($path));
    }
}
