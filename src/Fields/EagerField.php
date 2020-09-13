<?php

namespace Binaryk\LaravelRestify\Fields;

class EagerField extends Field
{
    /**
     * @var string
     */
    protected $relation;

    /**
     * @var string
     */
    protected $parentRepository;
}
