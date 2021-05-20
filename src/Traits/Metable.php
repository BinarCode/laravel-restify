<?php

namespace Binaryk\LaravelRestify\Traits;

trait Metable
{
    public array $meta = [];

    public function meta(): array
    {
        return $this->meta;
    }

    public function withMeta(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }
}
