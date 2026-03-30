<?php

namespace Binaryk\LaravelRestify\Filters;

use Illuminate\Support\Collection;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends Collection<TKey, TValue>
 */
class FiltersCollection extends Collection {}
