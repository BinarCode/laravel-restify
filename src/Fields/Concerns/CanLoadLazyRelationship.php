<?php

namespace Binaryk\LaravelRestify\Fields\Concerns;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

/**
 * @mixin Field
 */
trait CanLoadLazyRelationship
{
    protected ?string $lazyRelationshipName = null;

    public function lazy(string $relationshipName = null): self
    {
        $this->lazyRelationshipName = $relationshipName ?? $this->getAttribute();

        return $this;
    }

    public function isLazy(RestifyRequest $request): bool
    {
        return !is_null($this->lazyRelationshipName);
    }

    public function getLazyRelationshipName(): ?string
    {
        return $this->lazyRelationshipName;
    }
}
