<?php

namespace Binaryk\LaravelRestify\Tests\Assertables;

use Binaryk\LaravelRestify\Traits\Make;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Illuminate\Testing\Fluent\Concerns\Debugging;
use Illuminate\Testing\Fluent\Concerns\Has;
use Illuminate\Testing\Fluent\Concerns\Interaction;
use Illuminate\Testing\Fluent\Concerns\Matching;

use function PHPUnit\Framework\assertNotNull;

abstract class AssertableModel
{
    use CarbonMatching;
    use Debugging;
    use Has;
    use Interaction;
    use Macroable;
    use Make;
    use Matching;
    use Tappable;

    public function __construct(
        protected Model $model,
    ) {}

    public function first(?Closure $callback = null): Model
    {
        return $this->model;
    }

    protected function scope(string $key, Closure $callback)
    {
        return $this;
    }

    protected function dotPath(string $key = ''): string
    {
        return $key;
    }

    protected function prop(?string $key = null)
    {
        if (is_null($key)) {
            return $this->model->toArray();
        }

        return data_get($this->model, $key);
    }

    public function refresh(): self
    {
        $this->model = $this->model->refresh();

        return $this;
    }

    public function assertSoftDeleted(): self
    {
        assertNotNull(
            $this->model->getAttribute('deleted_at'),
        );

        return $this;
    }

    public function whereFloat(string $column, mixed $value): self
    {
        return $this->where($column, (float) $value);
    }

    abstract public function model();
}
