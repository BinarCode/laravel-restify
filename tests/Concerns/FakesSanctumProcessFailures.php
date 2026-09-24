<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Concerns;

use Binaryk\LaravelRestify\Commands\PrepareSanctumCommand;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @mixin TestCase
 */
trait FakesSanctumProcessFailures
{
    /**
     * Binds a `PrepareSanctumCommand` whose `runProcess()` never shells out to
     * a real `composer`/`artisan` binary: every call it does not need to fail
     * is a no-op, and the call whose command line contains
     * `$failingCommandNeedle` throws. Runs identically on every OS, unlike a
     * PATH-shimmed fake binary (which needs a `.bat`/`.cmd` on Windows).
     */
    protected function bindFailingSanctumProcess(string $failingCommandNeedle, string $errorOutput): void
    {
        $this->app->bind(PrepareSanctumCommand::class, fn () => new class($failingCommandNeedle, $errorOutput) extends PrepareSanctumCommand
        {
            public function __construct(
                private readonly string $failingCommandNeedle,
                private readonly string $errorOutput,
            ) {
                parent::__construct();
            }

            protected function runProcess(array $command): void
            {
                if (! str_contains(implode(' ', $command), $this->failingCommandNeedle)) {
                    return;
                }

                $process = new Process(['php', '-r', 'fwrite(STDERR, $argv[1]); exit(1);', $this->errorOutput]);
                $process->run();

                throw new ProcessFailedException($process);
            }
        });
    }
}
