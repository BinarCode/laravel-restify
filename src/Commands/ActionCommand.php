<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class ActionCommand extends GeneratorCommand
{
    use ConfirmableTrait;

    protected $name = 'restify:action';

    protected $description = 'Create a new action class';

    protected $type = 'Action';

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
        if (false === Str::endsWith($name, 'Action')) {
            $name .= 'Action';
        }

        return parent::buildClass($name);
    }

    protected function getStub()
    {
        return __DIR__.'/stubs/action.stub';
    }

    protected function getPath($name)
    {
        if (false === Str::endsWith($name, 'Action')) {
            $name .= 'Action';
        }

        return parent::getPath($name);
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Restify\Actions';
    }
}
