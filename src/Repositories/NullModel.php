<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Traits\InteractWithSQLight;
use Illuminate\Database\Eloquent\Model;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class NullModel extends Model
{
    use InteractWithSQLight;

    public $rows = [];
}
