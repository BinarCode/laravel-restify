<?php

namespace Binaryk\LaravelRestify\Cache;

use Carbon\CarbonInterface;

interface Cacheable
{
    public function cache(): ?CarbonInterface;
}
