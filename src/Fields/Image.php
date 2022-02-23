<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
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

        if ($this->value && Storage::disk($this->getStorageDisk())->exists($this->value)) {
            $this->value = Storage::disk($this->getStorageDisk())->url($this->value);
        } else {
            $this->value = $this->resolveDefaultValue(app(RestifyRequest::class));
        }

        return $this;
    }

    public function resolveForIndex($repository, $attribute = null)
    {
        parent::resolveForIndex($repository, $attribute);

        if ($this->value && Storage::disk($this->getStorageDisk())->exists($this->value)) {
            $this->value = Storage::disk($this->getStorageDisk())->url($this->value);
        } else {
            $this->value = $this->resolveDefaultValue(app(RestifyRequest::class));
        }

        return $this;
    }
}
