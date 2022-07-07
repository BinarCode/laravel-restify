<?php

namespace Binaryk\LaravelRestify\Tests\Assertables;

use Binaryk\LaravelRestify\Models\ActionLog;

class AssertableActionLog extends AssertableModel
{
    public function model(): ActionLog
    {
        return $this->model;
    }
}
