<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class PublishAuthControllerCommand extends Command
{
    protected $name = 'restify:publish-controllers';

    protected $description = 'Publish controllers from package to local project';

    public function handle()
    {
        $this
            ->publishControllers()
            ->publishBlades()
            ->publishEmails();


        $this->info('Restify Controllers & Emails published successfully');
    }

    public function publishControllers(): self
    {
        $path = 'Http/Controllers/Restify/Auth/';
        $stubDirectory = '/../Commands/stubs/Auth';
        $format = '.php';

        $this->checkDirectory($path)
            ->copyDirectory($path, $stubDirectory, $format);

        return $this;
    }

    public function publishBlades(): self
    {
        $path = '../resources/views/Restify/Auth/';
        $stubDirectory = '/../Commands/stubs/Blades';
        $format = '.blade.php';

        $this->checkDirectory($path)
            ->copyDirectory($path, $stubDirectory, $format);


        return $this;
    }

    public function publishEmails(): self
    {
        $path = 'Mail/Restify/Auth/';
        $stubDirectory = '/../Commands/stubs/Email';
        $format = '.php';

        $this->checkDirectory($path)
            ->copyDirectory($path, $stubDirectory, $format);
        return $this;
    }

    public function checkDirectory(string $path = null): self
    {

        if (!is_dir($directory = app_path($path))) {
            mkdir($directory, 0755, true);
        }

        return $this;
    }

    protected function copyDirectory(string $path, string $stubDirectory, string $format): self
    {
        $filesystem = new Filesystem;

        collect($filesystem->allFiles(__DIR__ . $stubDirectory))
            ->each(function (SplFileInfo $file) use ($filesystem, $path, $format, $stubDirectory) {

                $filesystem->copy(
                    $file->getPathname(),
                    $fullPath = app_path($path . Str::replaceLast('.stub', $format, $file->getFilename()))
                );

                $this->setNameSpace($stubDirectory, $file->getFilename(), $path, $fullPath);

            });

        return $this;
    }

    /**
     * Compiles the "HomeController" stub.
     *
     * @param string $stubDirectory
     * @param string $fileName
     * @return string
     */
    protected function setNameSpace(string $stubDirectory, string $fileName, string $path, string $fullPath): string
    {
        $path = substr(str_replace('/', '\\', $path), 0, -1);

        return file_put_contents($fullPath, str_replace(
            '{{namespace}}',
            $this->laravel->getNamespace() . $path,
            file_get_contents(__DIR__ . $stubDirectory . '/' . $fileName)
        ));
    }
}
