<?php

namespace Binaryk\LaravelRestify\Tests\Assertables;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use PHPUnit\Framework\Assert;

class AssertablePost extends AssertableModel
{
    public function hasActionLog(int $count = 1): self
    {
        Assert::assertCount($count, $this->model()->actionLogs()->get());

        return $this;
    }

    public function model(): Post
    {
        return $this->model;
    }
}
