<?php

namespace Binaryk\LaravelRestify\Tests\Assertables;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert as PHPUnit;

trait CarbonMatching
{
    /**
     * Asserts that the property matches the Carbon date.
     *
     * @param  mixed|\Closure  $expected
     * @return $this
     */
    public function whereDate(string $key, CarbonInterface $expected): self
    {
        $this->has($key);

        /** * @var CarbonInterface $actual */
        $actual = $this->prop($key);

        PHPUnit::assertInstanceOf(CarbonInterface::class, $actual);

        if ($expected instanceof Closure) {
            PHPUnit::assertTrue(
                $expected(is_array($actual) ? Collection::make($actual) : $actual),
                sprintf('Property [%s] was marked as invalid using a closure.', $this->dotPath($key))
            );

            return $this;
        }

        $this->ensureSorted($expected);
        $this->ensureSorted($actual);

        PHPUnit::assertSame(
            $actual->toDateString(),
            $expected->toDateString(),
            sprintf('Property [%s] does not match the expected value.', $this->dotPath($key))
        );

        return $this;
    }
}
