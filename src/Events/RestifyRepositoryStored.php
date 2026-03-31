<?php

namespace Binaryk\LaravelRestify\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class RestifyRepositoryStored
{
    use SerializesModels;

    /**
     * @var Model
     */
    public $model;

    public function __construct($model)
    {
        $this->model = $model;
    }
}
