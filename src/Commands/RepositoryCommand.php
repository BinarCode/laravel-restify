<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RepositoryCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'restify:repository';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Repository';

    /**
     * Execute the console command.
     *
     * @return bool|null
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        parent::handle();
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        $model = $this->option('model');

        if (is_null($model)) {
            $model = $this->laravel->getNamespace().$this->argument('name');
        } elseif (! Str::startsWith($model, [
            $this->laravel->getNamespace(), '\\',
        ])) {
            $model = $this->laravel->getNamespace().$model;
        }

        if ($this->option('all')) {
            $this->call('make:model', [
                'name' => $this->argument('name'),
                '--factory' => true,
                '--migration' => true,
                '--controller' => true,
            ]);

            $this->call('make:policy', [
                'name' => $this->argument('name').'Policy',
            ]);
        }

        return str_replace(
            'DummyFullModel', $model, parent::buildClass($name)
        );
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/repository.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Restify';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['all', 'a', InputOption::VALUE_NONE, 'Generate a migration, factory, and controller for the repository'],
            ['model', 'm', InputOption::VALUE_REQUIRED, 'The model class being represented.'],
        ];
    }
}
