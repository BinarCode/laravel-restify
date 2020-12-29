<?php

namespace Binaryk\LaravelRestify\Eager;

use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Traits\Make;

class Related implements \JsonSerializable
{
    use Make;

    private string $relation;

    private ?EagerField $field;

    public function __construct(string $relation, EagerField $field = null)
    {
        $this->relation = $relation;
        $this->field = $field;
    }

    public function isEager(): bool
    {
        return !is_null($this->field);
    }

    public function getRelation(): string
    {
        return $this->relation;
    }

    public function resolveField(Repository $repository): EagerField
    {
        return $this->field->resolve($repository);
    }

    public function jsonSerialize()
    {
        return [
            'relation' => $this->getRelation(),
            'field' => isset($this->field)
                ? $this->field->jsonSerialize()
                : null
        ];
    }
}
