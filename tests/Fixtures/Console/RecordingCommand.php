<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RecordingCommand extends Command
{
    /** @var list<string> */
    public static array $calls = [];

    /** @var array<string, int> */
    public static array $exitCodes = [];

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = (string) $this->getName();

        self::$calls[] = $name;

        return self::$exitCodes[$name] ?? self::SUCCESS;
    }
}
