<?php

namespace Binaryk\LaravelRestify\Filters;

class RelatedQuery
{
    public RelatedQueryCollection $nested;

    public string $tree;

    public bool $serialized = false;

    public function __construct(
        public string $relation,
        public bool $loaded = false,
        public array $columns = ['*'],
        RelatedQueryCollection $nested = null,
    ) {
        $this->nested = $nested ?? RelatedQueryCollection::make([]);
        $this->tree = $relation;
        $this->serialized = false;
    }

    public function columns(): array
    {
        return $this->columns;
    }

    public function notation(string $notation): self
    {
        $this->tree = $notation;

        return $this;
    }

    public function parent(string $parent): self
    {
        $this->tree = "$parent.$this->tree";

        return $this;
    }

    public function serialized(): self
    {
        $this->serialized = true;

        return $this;
    }

    public function isSerialized(): bool
    {
        return $this->serialized;
    }

    public function matchTree(string $tree): bool
    {
        return str($this->tree)->contains($tree);
    }

    public static function fromToken(string $token): RelatedQuery
    {
        if (str_contains($token, '[')) {
            // has columns
            return new RelatedQuery(
                relation: str($token)->before('['),
                columns: str($token)->between('[', ']')->explode('|')->all(),
            );
        }

        return new RelatedQuery($token);
    }
}
