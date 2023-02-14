<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class PublishAuthCommand extends Command
{
    protected $name = 'restify:auth';

    protected $description = 'Publish auth controllers & blades.';

    public function handle()
    {
        $this
            ->publishControllers()
            ->publishBlades()
            ->publishEmails()
            ->registerRoutes();

        $this->info('Restify Controllers & Emails published successfully');
    }

    /**
     * @return $this
     */
    public function publishControllers(): self
    {
        $path = 'Http/Controllers/Restify/Auth/';
        $stubDirectory = '/../Commands/stubs/Auth';
        $format = '.php';

        $this->checkDirectory($path)
            ->copyDirectory($path, $stubDirectory, $format);

        return $this;
    }

    /**
     * @return $this
     */
    public function publishBlades(): self
    {
        $path = '../resources/views/Restify/Auth/';
        $stubDirectory = '/../Commands/stubs/Blades';
        $format = '.blade.php';

        $this->checkDirectory($path)
            ->copyDirectory($path, $stubDirectory, $format);

        return $this;
    }

    /**
     * @return $this
     */
    public function publishEmails(): self
    {
        $path = 'Mail/Restify/Auth/';
        $stubDirectory = '/../Commands/stubs/Email';
        $format = '.php';

        $this->checkDirectory($path)
            ->copyDirectory($path, $stubDirectory, $format);

        return $this;
    }

    /**
     * @return $this
     */
    public function checkDirectory(string $path): self
    {
        if (! is_dir($directory = app_path($path))) {
            mkdir($directory, 0755, true);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function copyDirectory(string $path, string $stubDirectory, string $format): self
    {
        $filesystem = new Filesystem();

        collect($filesystem->allFiles(__DIR__.$stubDirectory))
            ->each(function (SplFileInfo $file) use ($filesystem, $path, $format, $stubDirectory) {
                $filesystem->copy(
                    $file->getPathname(),
                    $fullPath = app_path($path.Str::replaceLast('.stub', $format, $file->getFilename()))
                );

                $this->setNamespace($stubDirectory, $file->getFilename(), $path, $fullPath);
            });

        return $this;
    }

    protected function setNamespace(string $stubDirectory, string $fileName, string $path, string $fullPath): string
    {
        $path = substr(str_replace('/', '\\', $path), 0, -1);

        return file_put_contents($fullPath, str_replace(
            '{{namespace}}',
            $this->laravel->getNamespace().$path,
            file_get_contents(__DIR__.$stubDirectory.'/'.$fileName)
        ));
    }

    /**
     * @return $this
     */
    protected function registerRoutes(): self
    {
        $apiPath = base_path('routes/api.php');
        $initial = file_get_contents($apiPath);

        $initial = str($initial)->replace('Route::restifyAuth();', '')->toString();

        $file = fopen($apiPath, 'w');

        $routeStub = __DIR__.'/stubs/Routes/routes.stub';

        fwrite($file, $initial."\n".file_get_contents($routeStub));

        fclose($file);

        return $this;
    }
}
