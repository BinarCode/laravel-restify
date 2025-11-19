<?php

namespace Binaryk\LaravelRestify\Fields\Concerns;

use Binaryk\LaravelRestify\Contracts\Deletable as DeletableContract;
use Closure;

trait Deletable
{
    /**
     * The callback used to delete the field.
     *
     * @var callable
     */
    public $deleteCallback;

    /**
     * Indicates if the underlying field is deletable.
     *
     * @var bool
     */
    public $deletable = true;

    /**
     * Indicates if the underlying field is prunable.
     */
    public bool $prunable = false;

    /**
     * Specify the callback that should be used to delete the field.
     */
    public function delete(callable $deleteCallback): DeletableContract
    {
        $this->deleteCallback = $deleteCallback;

        return $this;
    }

    /**
     * Specify if the underlying file is able to be deleted.
     *
     * @param  bool  $deletable
     * @return $this
     */
    public function deletable($deletable = true): DeletableContract
    {
        $this->deletable = $deletable;

        return $this;
    }

    /**
     * Determine if the underlying file should be pruned when the resource is deleted.
     */
    public function isPrunable(): bool
    {
        return $this->prunable;
    }

    /**
     * Determine if the underlying file should be pruned when the resource is deleted.
     */
    public function isDeletable(): bool
    {
        return $this->deletable;
    }

    /**
     * Specify if the underlying file should be pruned when the resource is deleted.
     *
     * @param  bool  $prunable
     * @return $this
     */
    public function prunable($prunable = true): DeletableContract
    {
        $this->prunable = $prunable;

        return $this;
    }

    public function getDeleteCallback(): ?Closure
    {
        return $this->deleteCallback;
    }
}
