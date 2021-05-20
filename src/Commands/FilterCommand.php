<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class FilterCommand extends GeneratorCommand
{
    use ConfirmableTrait;

    protected $name = 'restify:filter';

    protected $description = 'Create a new filter class';

    protected $type = 'Filter';

    public function handle()
    {
        if (parent::handle() === false && ! $this->option('force')) {
            return false;
        }
    }

    protected function buildClass($name)
    {
        if (false === Str::endsWith($name, 'Filter')) {
            $name .= 'Filter';
        }

        return $this->replaceUsage($this->replaceModel(parent::buildClass($name)));
    }

    protected function replaceModel($stub)
    {
        return str_replace(['{{ parent }}', '{{parent}}'], $this->parent()['parent'], $stub);
    }

    protected function replaceUsage($stub)
    {
        return str_replace(['{{ usage }}', '{{usage}}'], $this->parent()['usage'], $stub);
    }

    protected function parent(): array
    {
        if ($this->option('sort')) {
            return [
                'parent' => 'SortableFilter',
                'usage' => 'use Binaryk\LaravelRestify\Filters\SortableFilter;',
                'namespace' => 'Sortables',
            ];
        }

        if ($this->option('search')) {
            return [
                'parent' => 'SearchableFilter',
                'usage' => 'use Binaryk\LaravelRestify\Filters\SearchableFilter;',
                'namespace' => 'Searchables',
            ];
        }

        if ($this->option('match')) {
            return [
                'parent' => 'MatchFilter',
                'usage' => 'use Binaryk\LaravelRestify\Filters\MatchFilter;',
                'namespace' => 'Matchers',
            ];
        }

        if ($this->option('bool')) {
            return [
                'parent' => 'BooleanFilter',
                'usage' => 'use Binaryk\LaravelRestify\Filters\BooleanFilter;',
                'namespace' => 'Filters',
            ];
        }

        if ($this->option('select')) {
            return [
                'parent' => 'SelectFilter',
                'usage' => 'use Binaryk\LaravelRestify\Filters\SelectFilter;',
                'namespace' => 'Filters',
            ];
        }

        if ($this->option('date')) {
            return [
                'parent' => 'TimestampFilter',
                'usage' => 'use Binaryk\LaravelRestify\Filters\TimestampFilter;',
                'namespace' => 'Filters',
            ];
        }

        return [
            'parent' => 'AdvancedFilter',
            'usage' => 'use Binaryk\LaravelRestify\Filters\AdvancedFilter;',
            'namespace' => 'Filters',
        ];
    }

    protected function getStub()
    {
        return __DIR__.'/stubs/filter.stub';
    }

    protected function getPath($name)
    {
        if (false === Str::endsWith($name, 'Filter')) {
            $name .= 'Filter';
        }

        return parent::getPath($name);
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\\Restify\\'.$this->parent()['namespace'];
    }

    protected function getOptions()
    {
        return [
            ['match', 'match', InputOption::VALUE_NONE, 'Generates a filter for match.'],
            ['sort', 'sort', InputOption::VALUE_NONE, 'Generates a filter for sorting.'],
            ['search', 'search', InputOption::VALUE_NONE, 'Generates a filter for search.'],
            ['bool', 'bool', InputOption::VALUE_NONE, 'Generates an advanced boolean filter.'],
            ['select', 'select', InputOption::VALUE_NONE, 'Generates an advanced select filter.'],
            ['date', 'date', InputOption::VALUE_NONE, 'Generates an advanced date filter..'],
        ];
    }
}
