<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class PolicyCommand extends GeneratorCommand
{
    protected $name = 'restify:policy';

    protected $description = 'Create a new policy for a specific model.';

    public function handle()
    {
        if (parent::handle() === false && ! $this->option('force')) {
            return false;
        }
    }

    protected function buildClass($name)
    {
        $class = $this->replaceModel(parent::buildClass($name));

        $class = $this->replaceQualifiedModel($class);

        return $class;
    }

    protected function replaceClass($stub, $name)
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $this->guessPolicyName());

        return str_replace(['{{ class }}', '{{class}}'], $class, $stub);
    }

    protected function replaceModel($stub)
    {
        return str_replace(['{{ model }}', '{{model}}'], class_basename($this->guessQualifiedModel()), $stub);
    }

    protected function replaceQualifiedModel($stub)
    {
        return str_replace('{{ modelQualified }}', $this->guessQualifiedModel(), $stub);
    }

    protected function guessQualifiedModel(): string
    {
        $model = Str::singular(class_basename(Str::beforeLast($this->getNameInput(), 'Policy')));

        return str_replace('/', '\\', $this->rootNamespace().'Models/'.$model);
    }

    protected function guessPolicyName()
    {
        $name = $this->getNameInput();

        if (false === Str::endsWith($name, 'Policy')) {
            $name .= 'Policy';
        }

        return $name;
    }

    protected function getPath($name)
    {
        return $this->laravel['path'].'/Policies/'.$this->guessPolicyName().'.php';
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/policy.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Policies';
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
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the model already exists.'],
        ];
    }
}
