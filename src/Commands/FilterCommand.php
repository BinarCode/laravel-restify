<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class FilterCommand extends GeneratorCommand
{
    use ConfirmableTrait;

    protected $name = 'restify:filter';

    protected $description = 'Create a new filter class';

    protected $type = 'Filter';

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
     * @param string $name
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        if (false === Str::endsWith($name, 'Filter')) {
            $name .= 'Filter';
        }

        return tap(parent::buildClass($name), function ($stub) use ($name) {
            return str_replace(['{{ attribute }}', '{{ query }}'], Str::snake(
                Str::beforeLast(class_basename($name), 'Filter')
            ), $stub);
        });
    }

    protected function getStub()
    {
        return __DIR__.'/stubs/filter.stub';
    }

    protected function getPath($name)
    {
        if (false === Str::endsWith($name, 'Filter')) {
            $name .= 'Filter';
        }

        return parent::getPath($name);
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Restify\Matchers';
    }
}
