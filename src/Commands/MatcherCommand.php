<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class MatcherCommand extends GeneratorCommand
{
    use ConfirmableTrait;

    protected $name = 'restify:matcher';

    protected $description = 'Create a new matcher class';

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
     * @param string $name
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        if (false === Str::endsWith($name, 'Match')) {
            $name .= 'Match';
        }

        return tap(parent::buildClass($name), function ($stub) use ($name) {
            return str_replace(['{{ attribute }}', '{{ query }}'], Str::snake(
                Str::beforeLast(class_basename($name), 'Match')
            ), $stub);
        });
    }

    protected function getStub()
    {
        return __DIR__ . '/stubs/match.stub';
    }

    protected function getPath($name)
    {
        if (false === Str::endsWith($name, 'Match')) {
            $name .= 'Match';
        }

        return parent::getPath($name);
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Restify\Matchers';
    }
}
