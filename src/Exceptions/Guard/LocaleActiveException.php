<?php

namespace Binaryk\LaravelRestify\Exceptions\Guard;

class LocaleActiveException extends \Exception
{
    /**
     * Create a new exception instance.
     *
     * @param string $locale
     * @return void
     */
    public function __construct(string $locale)
    {
        parent::__construct("Provided locale '{$locale}' is not active.");
    }
}
