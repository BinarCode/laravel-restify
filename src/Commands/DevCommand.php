<?php

namespace Binaryk\LaravelRestify\Commands;

use Faker\Generator as Faker;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class DevCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'restify:dev {--path= : The path to the root local directory.}
                                        {--git : Use the latest vcs git repository}
    ';

    protected $description = 'Add laravel-restify from a local directory.';

    /** * @var Faker */
    private $faker;

    public function __construct(Resolver $resolver, Faker $faker)
    {
        parent::__construct();
        $this->resolver = $resolver;
        $this->faker = $faker;
    }

    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return true;
        }

        $this->addRepositoryToRootComposer();

        $this->info('Added local path to repositories.');

        $this->addPackageToRootComposer();

        $this->info('Package added to the root composer as *.');

        $this->composerUpdate();

        $this->info('Composer updated.');

        return 0;
    }

    protected function addPackageToRootComposer()
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);

        $composer['require']['binaryk/laravel-restify'] = $this->resolveVersion();

        file_put_contents(
            base_path('composer.json'),
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    protected function addRepositoryToRootComposer()
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);

        if (array_key_exists('repositories', $composer)) {
            $composer['repositories'] = collect($composer['repositories'])->filter(function ($repository) {
                if (! array_key_exists('url', $repository)) {
                    return true;
                }

                $pathIsAlreadyInRepositories = Str::contains(
                    $repository['url'],
                    $this->resolvePath()
                );

                if ($pathIsAlreadyInRepositories) {
                    return false;
                }

                return ! Str::contains($repository['url'], 'laravel-restify');
            })->values()->toArray();
        }

        if ($this->option('git')) {
            $composer['repositories'][] = [
                'type' => 'vcs',
                'url' => $this->option('path') ?: 'git@github.com:BinarCode/laravel-restify.git',
            ];
        } else {
            $composer['repositories'][] = [
                'type' => 'path',
                'url' => $this->option('path') ?: '../../binarcode/laravel-restify',
            ];
        }

        file_put_contents(
            base_path('composer.json'),
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    protected function composerUpdate()
    {
        $this->executeCommand('composer update binaryk/laravel-restify', getcwd());
    }

    protected function executeCommand($command, $path)
    {
        $process = (Process::fromShellCommandline($command, $path))->setTimeout(null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        $process->run(function ($type, $line) {
            $this->output->write($line);
        });
    }

    public function resolveVersion(): string
    {
        return $this->option('git')
            ? '3.x-dev'
            : '*';
    }

    public function resolvePath()
    {
        return $this->option('path') ?: $this->resolveDefaultPath();
    }

    private function resolveDefaultPath()
    {
        return $this->option('git')
            ? 'git@github.com:BinarCode/laravel-restify.git'
            : '../../binarcode/laravel-restify';
    }
}
