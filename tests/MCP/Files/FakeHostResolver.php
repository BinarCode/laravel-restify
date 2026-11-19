<?php

namespace Binaryk\LaravelRestify\Tests\MCP\Files;

use Binaryk\LaravelRestify\MCP\Files\HostResolver;

/**
 * Answers each lookup with the next queued answer, and repeats the last one once the queue is used up.
 * A queue of two different answers is a host that changes its mind between two lookups.
 */
final class FakeHostResolver implements HostResolver
{
    /**
     * @var list<string>
     */
    public array $lookups = [];

    /**
     * @var list<list<string>>
     */
    private array $answers;

    /**
     * @param  list<string>  ...$answers
     */
    public function __construct(array ...$answers)
    {
        $this->answers = array_values($answers);
    }

    public function resolve(string $host): array
    {
        $this->lookups[] = $host;

        return count($this->answers) > 1
            ? array_shift($this->answers)
            : ($this->answers[0] ?? []);
    }
}
