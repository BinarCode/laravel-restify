<?php

namespace Binaryk\LaravelRestify\Commands;

use Binaryk\LaravelRestify\Contracts\Passportable;
use Illuminate\Config\Repository;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Psr\Container\NotFoundExceptionInterface;

class CheckPassport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'restify:check-passport';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if Passport personal token is configured';

    /**
     * @var Repository
     */
    protected $config;
    /**
     * @var Application
     */
    protected $app;
    /**
     * @var Container
     */
    protected $container;

    /**
     * Create a new command instance.
     *
     * @param Repository $config
     * @param Container $container
     * @param Application $app
     */
    public function __construct(Repository $config, Container $container, Application $app)
    {
        parent::__construct();
        $this->config = $config;
        $this->container = $container;
        $this->app = $app;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $userClass = $this->config->get('auth.providers.users.model');

        if (false === $this->hasProvider()) {
            return;
        }

        if (false === $this->isPassportable($userClass)) {
            return;
        }

        if (false === $this->hasPassportClient()) {
            return;
        }

        if ($this->config->get('auth.guards.api.driver') !== 'passport') {
            $this->warn("This configuration 'auth.guards.api.driver' should be 'passport'");

            return;
        }

        $this->info('You have green light! Passport configured properly.');
    }

    /**
     * @param $userClass
     * @return bool
     */
    public function isPassportable($userClass = null): bool
    {
        try {
            $userInstance = $this->container->get($userClass);

            if (false === $userInstance instanceof Passportable) {
                $this->warn("$userClass model should implement \Binaryk\LaravelRestify\Contracts\Passportable");

                return false;
            }
        } catch (NotFoundExceptionInterface $e) {
            $this->warn("The model from the follow configuration -> 'auth.providers.users.model' doesn't exists.");

            return false;
        }

        if (false === in_array('Laravel\\Passport\\HasApiTokens', (new \ReflectionClass($userClass))->getTraitNames())) {
            $this->warn("Your $userClass class should use 'Laravel\\Passport\\HasApiTokens' trait.");
            $this->warn('See: https://laravel.com/docs/6.x/passport#introduction');

            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function hasProvider(): bool
    {
        $provider = $this->app->getProviders('Laravel\\Passport\\PassportServiceProvider');

        if (count($provider) === 0) {
            $this->warn('Please follow the passport installation: https://laravel.com/docs/6.x/passport#introduction');

            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function hasPassportClient(): bool
    {
        try {
            /**
             * @var \Laravel\Passport\ClientRepository
             */
            $clientsRepository = $this->container->get('\\Laravel\\Passport\\ClientRepository');
            $clientsRepository->personalAccessClient();
        } catch (NotFoundExceptionInterface $e) {
            $this->warn('Repository for managing clients not found: \\Laravel\\Passport\\ClientRepository');
            $this->warn('Make sure you run: php artisan passport:install');

            return false;
        } catch (\RuntimeException $e) {
            $this->warn($e->getMessage());
            $this->warn('Hint: php artisan passport:client --personal');
            $this->warn('See: https://laravel.com/docs/6.x/passport#creating-a-personal-access-client');

            return false;
        }

        return true;
    }
}
