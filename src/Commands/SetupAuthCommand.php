<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\Command;

class SetupAuthCommand extends Command
{
    protected $signature = 'restify:setup-auth';

    protected $description = 'Configure Sanctum and add auth routes';

    public function handle(): int
    {
        $this->info('Configure Sanctum and add auth routes');

        $sanctumExitCode = $this->call(PrepareSanctumCommand::class);
        $authMacroExitCode = $this->call(RestifyAuthMacroCommand::class);

        return $sanctumExitCode === self::SUCCESS && $authMacroExitCode === self::SUCCESS
            ? self::SUCCESS
            : self::FAILURE;
    }
}
