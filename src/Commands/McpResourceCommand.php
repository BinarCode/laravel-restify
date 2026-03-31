<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;

class McpResourceCommand extends GeneratorCommand
{
    use ConfirmableTrait;

    protected $name = 'restify:mcp-resource';

    protected $description = 'Create a new MCP resource class';

    protected $type = 'Resource';

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
        if (Str::endsWith($name, 'Resource') === false) {
            $name .= 'Resource';
        }

        return parent::buildClass($name);
    }

    protected function getStub()
    {
        return __DIR__.'/stubs/mcp-resource.stub';
    }

    protected function getPath($name)
    {
        if (Str::endsWith($name, 'Resource') === false) {
            $name .= 'Resource';
        }

        return parent::getPath($name);
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\\Restify\\Mcp\\Resources';
    }
}
