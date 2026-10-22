<?php

namespace Binaryk\LaravelRestify\Contracts;

/**
 * A pivot whose table has a primary key column (integer or string, e.g. a ULID) that the model
 * does not declare through $incrementing or HasUlids/HasUuids. Sorted index queries end with it.
 */
interface KeyedPivot {}
