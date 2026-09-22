<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A spy that stands in for a real artisan command, so a test can assert the
 * exact order another command calls it in.
 */
final class RecordingCommand extends Command
{
    /** @var list<string> */
    public static array $calls = [];

    public function __construct(string $name)
    {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        self::$calls[] = (string) $this->getName();

        return self::SUCCESS;
    }
}
