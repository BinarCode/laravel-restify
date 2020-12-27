<?php

namespace Binaryk\LaravelRestify\Fields;

use Illuminate\Support\Facades\Storage;

class Image extends File
{
    public function __construct($attribute, callable $resolveCallback = null)
    {
        parent::__construct($attribute, $resolveCallback);

        $this->acceptedTypes('image/*');
    }

    public function resolveForShow($repository, $attribute = null)
    {
        parent::resolveForShow($repository, $attribute);

        $this->value = Storage::disk($this->getStorageDisk())->url($this->value);

        return $this;
    }

    public function resolveForIndex($repository, $attribute = null)
    {
        parent::resolveForIndex($repository, $attribute);

        $this->value = Storage::disk($this->getStorageDisk())->url($this->value);

        return $this;
    }
}
