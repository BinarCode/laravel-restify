<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Foundation\Console\RouteListCommand;
use Illuminate\Routing\Route;
use Symfony\Component\Console\Input\InputOption;

class RestifyRouteListCommand extends RouteListCommand
{
    protected $name = 'restify:routes';

    protected $description = 'Display all registered Restify routes.';

    protected function getRoutes()
    {
        $base = str(config('restify.base'))
            ->whenStartsWith('/', fn ($replace) => $replace->replaceFirst('/', ''))
            ->toString();

        $routes = collect($this->router->getRoutes())
            ->filter(function (Route $route) use ($base) {
                return strpos($route->uri(), $base) === 0;
            })
            ->map(function ($route) {
                return $this->getRouteInformation($route);
            })->filter()->all();

        if (($sort = $this->option('sort')) !== null) {
            $routes = $this->sortRoutes($sort, $routes);
        } else {
            $routes = $this->sortRoutes('uri', $routes);
        }

        if ($this->option('reverse')) {
            $routes = array_reverse($routes);
        }

        return $this->pluckColumns($routes);
    }

    protected function configureOptions()
    {
        parent::configureOptions();

        $this->addOption('command', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by the given command.');
    }
}
