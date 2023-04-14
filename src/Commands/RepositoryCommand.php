<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class RepositoryCommand extends GeneratorCommand
{
    use ConfirmableTrait;

    protected $name = 'restify:repository';

    protected $description = 'Create a new repository class';

    protected $type = 'Repository';

    public function handle()
    {
        if (parent::handle() === false && ! $this->option('force')) {
            return false;
        }

        $this->callSilent('restify:base-repository', [
            'name' => 'Repository',
        ]);

        if ($this->option('all')) {
            $this->input->setOption('factory', true);
            $this->input->setOption('model', true);
            $this->input->setOption('policy', true);
            $this->input->setOption('table', true);
        }

        if ($this->option('policy')) {
            $this->buildPolicy();
        }

        if ($this->option('model')) {
            $this->buildModel();
        }

        if ($this->option('table')) {
            $this->buildMigration();
        }

        if ($this->option('factory')) {
            $this->buildFactory();
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
        if (false === Str::endsWith($name, 'Repository')) {
            $name .= 'Repository';
        }

        return $this->replaceModel(parent::buildClass($name), $this->guessBaseModelClass());
    }

    protected function replaceModel($stub, $class)
    {
        $model = str_replace(['DummyClass', '{{ modelBase }}', '{{modelBase}}'], "$class::class", $stub);

        return str_replace(['DummyClass', '{{ model }}', '{{model}}'], str($this->guessQualifiedModelName())->replace('\\\\', '\\').';', $model);
    }

    protected function guessBaseModelClass()
    {
        return class_basename($this->guessQualifiedModelName());
    }

    protected function guessQualifiedModelName()
    {
        $model = Str::singular(class_basename(Str::before($this->getNameInput(), 'Repository')));

        return str_replace('/', '\\', $this->rootNamespace().'/Models//'.$model);
    }

    protected function buildMigration()
    {
        $table = Str::snake(Str::pluralStudly(class_basename($this->guessQualifiedModelName())));

        $guessMigration = 'Create'.Str::studly($table).'Table';

        if (false === class_exists($guessMigration)) {
            $migration = Str::snake($guessMigration);
            $yes = $this->confirm("Do you want to generate the migration [{$migration}]?");

            if ($yes) {
                $this->call('make:migration', [
                    'name' => $migration,
                    '--create' => $table,
                ]);
            }
        }
    }

    protected function buildPolicy()
    {
        $this->call('restify:policy', [
            'name' => $this->guessBaseModelClass(),
        ]);

        return $this;
    }

    protected function buildModel()
    {
        $model = $this->guessQualifiedModelName();

        if (false === class_exists($model)) {
            $yes = $this->confirm("Do you want to generate the model [{$model}]?");

            if ($yes) {
                $this->call('make:model', ['name' => str_replace('\\\\', '\\', $model)]);
            }
        }
    }

    protected function buildFactory()
    {
        $factory = Str::studly(class_basename($this->guessQualifiedModelName()));

        $this->call('make:factory', [
            'name' => "{$factory}Factory",
            '--model' => str_replace('\\\\', '\\', $this->guessQualifiedModelName()),
        ]);
    }

    protected function getStub()
    {
        return __DIR__.'/stubs/repository.stub';
    }

    protected function getPath($name)
    {
        if (false === Str::endsWith($name, 'Repository')) {
            $name .= 'Repository';
        }

        return parent::getPath($name);
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Restify';
    }

    protected function getOptions()
    {
        return [
            ['all', 'a', InputOption::VALUE_NONE, 'Generate a migration, factory, and controller for the repository'],
            ['model', 'm', InputOption::VALUE_NONE, 'The model class being represented.'],
            ['factory', 'f', InputOption::VALUE_NONE, 'Create a new factory for the repository model.'],
            ['policy', 'p', InputOption::VALUE_NONE, 'Create a new policy for the repository model.'],
            ['table', 't', InputOption::VALUE_NONE, 'Create a new migration table file for the repository model.'],
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the model already exists.'],
        ];
    }
}
