<?php

namespace Binaryk\LaravelRestify\Filters;

trait HasMode
{
    public string $mode = 'strict';

    public function strict(): self
    {
        $this->mode = 'strict';

        return $this;
    }

    public function partial(): self
    {
        $this->mode = 'partial';

        return $this;
    }

    public function getNotLikeOperator(): string
    {
        if ($this->mode === 'strict') {
            return '!=';
        }

        return 'NOT LIKE';
    }

    public function getNotLikeValue(string $value): string
    {
        if ($this->mode === 'strict') {
            return $value;
        }

        return "%{$value}%";
    }

    public function getLikeOperator(): string
    {
        if ($this->mode === 'strict') {
            return '=';
        }

        return 'LIKE'; //TODO: ilike support
    }

    public function getLikeValue(string $value): string
    {
        if ($this->mode === 'strict') {
            return $value;
        }

        return "%{$value}%";
    }
}
