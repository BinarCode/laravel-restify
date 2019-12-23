<?php

namespace Binaryk\LaravelRestify\Services\Search;

abstract class Searchable
{
    /**
     * @var array|null
     */
    protected $fixedInput;

    /**
     * @param $input
     *
     * @return $this
     */
    public function setPredefinedFilters($input)
    {
        if (is_array($input)) {
            $this->fixedInput = $input;
        }

        return $this;
    }

    /**
     * @return static
     */
    public static function instance()
    {
        return new static;
    }
}
