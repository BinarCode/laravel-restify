<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class StoreCommand extends GeneratorCommand
{
    use ConfirmableTrait;

    protected $name = 'restify:store';

    protected $description = 'Create a new store class';

    protected $type = 'Matcher';

    public function handle()
    {
        if (parent::handle() === false && ! $this->option('force')) {
            return false;
        }
    }

    /**
     * Build the class with the given name.
     * This method should return the file class content.
     *
     * @param  string  $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        if (false === Str::endsWith($name, 'Store')) {
            $name .= 'Store';
        }

        return tap(parent::buildClass($name), function ($stub) use ($name) {
            return str_replace(['{{ attribute }}', '{{ query }}'], Str::snake(
                Str::beforeLast(class_basename($name), 'Store')
            ), $stub);
        });
    }

    protected function getStub()
    {
        return __DIR__.'/stubs/store.stub';
    }

    protected function getPath($name)
    {
        if (false === Str::endsWith($name, 'Store')) {
            $name .= 'Store';
        }

        return parent::getPath($name);
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Restify\Stores';
    }
}
