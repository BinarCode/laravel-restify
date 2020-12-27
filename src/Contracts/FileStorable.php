<?php

namespace Binaryk\LaravelRestify\Contracts;

interface FileStorable
{
    /**
     * Get the disk that the field is stored on.
     *
     * @return string|null
     */
    public function getStorageDisk();

    /**
     * Get the dir that the field is stored at on disk.
     *
     * @return string|null
     */
    public function getStorageDir();

    /**
     * Get the full path that the field is stored at on disk.
     *
     * @return string|null
     */
    public function getStoragePath();
}
