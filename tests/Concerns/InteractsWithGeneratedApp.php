<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Concerns;

use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;

/**
 * Redirects `app_path()` to a throwaway directory so generator commands
 * (which extend Illuminate\Console\GeneratorCommand and always write under
 * `$this->laravel['path']`) never touch the real application skeleton.
 *
 * @mixin TestCase
 */
trait InteractsWithGeneratedApp
{
    protected string $generatedAppPath;

    protected string $generatedDatabasePath;

    protected function setUpInteractsWithGeneratedApp(): void
    {
        // Cache the real "App\" namespace while `app_path()` still matches
        // the testbench skeleton's composer.json, before it gets redirected.
        app()->getNamespace();

        $unique = getmypid().'-'.uniqid('', true);

        $this->generatedAppPath = sys_get_temp_dir().'/restify-generated-app-'.$unique;
        $this->generatedDatabasePath = sys_get_temp_dir().'/restify-generated-database-'.$unique;

        File::makeDirectory($this->generatedAppPath, 0755, true);
        File::makeDirectory($this->generatedDatabasePath, 0755, true);

        app()->useAppPath($this->generatedAppPath);
        app()->useDatabasePath($this->generatedDatabasePath);
    }

    protected function tearDownInteractsWithGeneratedApp(): void
    {
        File::deleteDirectory($this->generatedAppPath);
        File::deleteDirectory($this->generatedDatabasePath);
    }
}
