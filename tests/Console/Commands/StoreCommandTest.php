<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithGeneratedApp;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class StoreCommandTest extends IntegrationTestCase
{
    use InteractsWithGeneratedApp;

    #[Test]
    public function it_generates_a_store_class(): void
    {
        $this->artisan('restify:store', ['name' => 'Avatar'])
            ->expectsOutputToContain('created successfully')
            ->assertExitCode(0);

        $path = $this->generatedAppPath.'/Restify/Stores/AvatarStore.php';
        $this->assertFileExists($path);

        $content = File::get($path);
        $this->assertStringContainsString('namespace App\Restify\Stores;', $content);
        $this->assertStringContainsString('class AvatarStore implements Storable', $content);
        $this->assertStringContainsString('public function handle(Request $request, Model $model, $attribute): array', $content);
    }

    #[Test]
    public function it_does_not_double_append_the_store_suffix(): void
    {
        $this->artisan('restify:store', ['name' => 'AvatarStore'])
            ->assertExitCode(0);

        $this->assertFileExists($this->generatedAppPath.'/Restify/Stores/AvatarStore.php');
    }

    #[Test]
    public function it_refuses_to_overwrite_an_existing_store_without_force(): void
    {
        $path = $this->generatedAppPath.'/Restify/Stores/AvatarStore.php';
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'original content');

        $this->artisan('restify:store', ['name' => 'Avatar'])
            ->expectsOutputToContain('Matcher already exists.')
            ->assertExitCode(0);

        $this->assertSame('original content', File::get($path));
    }

    #[Test]
    public function it_overwrites_an_existing_store_with_force(): void
    {
        $path = $this->generatedAppPath.'/Restify/Stores/AvatarStore.php';
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'original content');

        $this->artisan('restify:store', ['name' => 'Avatar', '--force' => true])
            ->expectsOutputToContain('created successfully')
            ->assertExitCode(0);

        $this->assertStringContainsString('class AvatarStore implements Storable', File::get($path));
    }

    #[Test]
    public function it_rejects_a_reserved_php_name(): void
    {
        $this->artisan('restify:store', ['name' => 'class'])
            ->expectsOutputToContain('reserved by PHP')
            ->assertExitCode(0);

        $this->assertDirectoryDoesNotExist($this->generatedAppPath.'/Restify/Stores');
    }
}
