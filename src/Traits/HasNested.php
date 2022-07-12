<?php

namespace Binaryk\LaravelRestify\Traits;

trait HasNested
{
    public array $nested = [];

    public function nested(array $nested = []): self
    {
        $this->nested = $nested;

        return $this;
    }

    public function getNested(): array
    {
        return $this->nested;
    }

    public function hasNested(): bool
    {
        return ! empty($this->nested);
    }
}
