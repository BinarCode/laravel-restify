<?php

namespace Binaryk\LaravelRestify\Fields;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
abstract class OrganicField extends BaseField
{
    /**
     * Rules for applied when store.
     *
     * @var array
     */
    public $storingRules = [];

    /**
     * Rules for applied when update model.
     * @var array
     */
    public $updatingRules = [];

    /**
     * Rules for applied when store and update.
     *
     * @var array
     */
    public $rules = [];

    /**
     * @var array
     */
    public $messages = [];
}
