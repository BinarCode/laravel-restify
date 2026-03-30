<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;

class ToolCommand extends GeneratorCommand
{
    use ConfirmableTrait;

    protected $name = 'restify:mcp-tool';

    protected $description = 'Create a new MCP tool class';

    protected $type = 'Tool';

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
     * @throws FileNotFoundException
     */
    protected function buildClass($name)
    {
        if (Str::endsWith($name, 'Tool') === false) {
            $name .= 'Tool';
        }

        $stub = parent::buildClass($name);

        // Replace the tool name placeholder with a kebab-case version of the class name
        $toolName = Str::kebab(Str::beforeLast(class_basename($name), 'Tool'));
        $stub = str_replace('DummyToolName', $toolName, $stub);

        return $stub;
    }

    protected function getStub()
    {
        return __DIR__.'/stubs/mcp-tool.stub';
    }

    protected function getPath($name)
    {
        if (Str::endsWith($name, 'Tool') === false) {
            $name .= 'Tool';
        }

        return parent::getPath($name);
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\\Restify\\Mcp\\Tools';
    }
}
