<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class PolicyCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'restify:policy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new policy for a specific model.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Policy';

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
        $namespacedModel = null;
        $model = $this->option('model');

        if (is_null($model)) {
            $model = $this->argument('name');
        }

        if ($model && ! Str::startsWith($model, [$this->laravel->getNamespace(), '\\',])) {
            $namespacedModel = $this->laravel->getNamespace() . $model;
        }

        $name .= 'Policy';

        $rendered = str_replace(
            'UseDummyModel', $namespacedModel ?? $model, parent::buildClass($name)
        );

        $rendered = str_replace(
            'DummyModel', $model, $rendered
        );

        return $rendered;
    }

    public function nameWithEnd()
    {
        $model = $this->option('model');

        if (is_null($model)) {
            $model = $this->argument('name');
        }

        return $model . 'Policy';
    }

    protected function getPath($name)
    {
        return $this->laravel['path'].'/Policies/'.$this->nameWithEnd() . '.php';
    }


    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/policy.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Restify';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_REQUIRED, 'The model class being protected.'],
        ];
    }
}
