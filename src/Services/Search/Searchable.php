<?php

namespace Binaryk\LaravelRestify\Services\Search;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Illuminate\Http\Request;

/**
 * @package Binaryk\LaravelRestify\Services\Search;
 */
abstract class Searchable
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var RestifySearchable $model
     */
    protected $model;

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
}
