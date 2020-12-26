<?php

namespace Binaryk\LaravelRestify\Contracts;

use Closure;

interface Deletable
{
    /**
     * Specify the callback that should be used to delete the field.
     *
     * @param  callable  $deleteCallback
     * @return $this
     */
    public function delete(callable $deleteCallback): self;

    /**
     * Return the deletable callback.
     *
     * @return Closure|null
     */
    public function getDeleteCallback(): ?Closure;

    /**
     * Specify if the field is able to be deleted.
     *
     * @param  bool  $deletable
     * @return $this
     */
    public function deletable($deletable = true): self;

    /**
     * Determine if the field should be pruned when the resource is deleted.
     *
     * @return bool
     */
    public function isPrunable(): bool;

    /**
     * Determine if the field can be deleted.
     *
     * @return bool
     */
    public function isDeletable(): bool;

    /**
     * Specify if the field should be pruned when the resource is deleted.
     *
     * @param  bool  $prunable
     * @return $this
     */
    public function prunable($prunable = true): self;
}
