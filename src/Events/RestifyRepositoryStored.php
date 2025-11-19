<?php

namespace Binaryk\LaravelRestify\Events;

use Illuminate\Queue\SerializesModels;

class RestifyRepositoryStored
{
    use SerializesModels;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $model;

    public function __construct($model)
    {
        $this->model = $model;
    }
}
