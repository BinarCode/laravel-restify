<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class GetterCommand extends GeneratorCommand
{
    use ConfirmableTrait;

    protected $name = 'restify:getter';

    protected $description = 'Create a new getter class';

    protected $type = 'Getter';

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
        if (false === Str::endsWith($name, 'Getter')) {
            $name .= 'Getter';
        }

        return parent::buildClass($name);
    }

    protected function getStub()
    {
        return __DIR__.'/stubs/getter.stub';
    }

    protected function getPath($name)
    {
        if (false === Str::endsWith($name, 'Getter')) {
            $name .= 'Getter';
        }

        return parent::getPath($name);
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Restify\Getters';
    }
}
