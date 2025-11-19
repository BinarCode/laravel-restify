<?php

namespace Binaryk\LaravelRestify\Traits;

use Binaryk\LaravelRestify\Filters\RelatedQueryCollection;

trait HasNested
{
    public ?RelatedQueryCollection $nested = null;

    //    public function nested(?RelatedQueryCollection $nested = null): self
    //    {
    //        $this->nested = $nested;
    //
    //        return $this;
    //    }

    //    public function getNested(): RelatedQueryCollection
    //    {
    //        return $this->nested ?? RelatedQueryCollection::make([]);
    //    }
    //
    public function hasNested(): bool
    {
        return ! empty($this->nested);
    }
}
