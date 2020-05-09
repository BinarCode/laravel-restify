<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\GeneratorCommand;

class BaseRepositoryCommand extends GeneratorCommand
{
    protected $name = 'restify:base-repository';

    protected $description = 'Create a new base repository class';

    protected $hidden = true;

    protected $type = 'Repository';

    public function handle()
    {
        parent::handle();
    }

    protected function getStub()
    {
        return __DIR__.'/stubs/base-repository.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Restify';
    }
}
