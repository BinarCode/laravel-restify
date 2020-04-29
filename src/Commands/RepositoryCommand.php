<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RepositoryCommand extends GeneratorCommand
{
    use ConfirmableTrait;

    protected $name = 'restify:repository';

    protected $description = 'Create a new repository class';

    protected $type = 'Repository';

    public function handle()
    {
        parent::handle();

        $this->callSilent('restify:base-repository', [
            'name' => 'Repository',
        ]);

        $this->buildModel();
        $this->buildMigration();
        $this->tryAll();
    }

    /**
     * Build the class with the given name.
     * This method should return the file class content
     *
     * @param string $name
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        $model = $this->guessQualifiedModelName($this->option('model'));

        return str_replace('DummyFullModel', $model, parent::buildClass($name));
    }

    protected function guessQualifiedModelName($name = null)
    {
        $class = trim($this->option('model')) ?: null;
        $model = Str::singular(Str::before($this->getNameInput(), 'Repository'));

        if ($userDidntPassModel = is_null($class)) {
            /*Check if the user didnt provide the model name*/
            return str_replace('/', '\\', $this->rootNamespace() . '/Models//' . $model);
        }

        if (Str::startsWith($class, $this->rootNamespace())) {
            return str_replace('\\', '\\\\', $class);
        }

        /* * Assuming the class model doesn't contain the namespace * * */
        return str_replace('/', '\\', $this->rootNamespace() . '/Models//' . $class);
    }

    protected function buildMigration()
    {
        $table = Str::snake(Str::pluralStudly(class_basename($this->guessQualifiedModelName())));

        $guessMigration = 'Create' . Str::studly($table) . 'Table';

        if (false === class_exists($guessMigration)) {
            $migration = Str::snake($guessMigration);
            $yes = $this->confirm("Do you want to generate the migration [{$migration}]?");

            if ($yes) {
                $this->call('make:migration', [
                    'name' => $migration,
                    '--create' => $table
                ]);
            }
        }
    }

    protected function buildModel()
    {
        $model = $this->guessQualifiedModelName();

        if (false === class_exists($model)) {
            $yes = $this->confirm("Do you want to generate the model [{$model}]?");

            if ($yes) {
                $this->call('make:model', ['name' => str_replace('\\\\', '\\', $model),]);
            }
        }
    }

    public function tryAll()
    {
        if ($this->option('all')) {
            $this->call('make:model', [
                'name' => $this->guessQualifiedModelName(),
                '--factory' => true,
                '--migration' => true,
                '--controller' => true,
            ]);

            $this->call('restify:policy', [
                'name' => $this->argument('name'),
            ]);
        }
    }

    protected function getStub()
    {
        return __DIR__ . '/stubs/repository.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Restify';
    }

    protected function getOptions()
    {
        return [
            ['all', 'a', InputOption::VALUE_NONE, 'Generate a migration, factory, and controller for the repository'],
            ['model', 'm', InputOption::VALUE_REQUIRED, 'The model class being represented.'],
        ];
    }
}
