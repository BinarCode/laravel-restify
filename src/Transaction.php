<?php

namespace Binaryk\LaravelRestify;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class Transaction
{
    /**
     * Perform the given callbacks within a batch transaction.
     *
     * @param  callable  $callback
     * @param  callable|null  $finished
     * @return mixed
     */
    public static function run($callback, $finished = null)
    {
        try {
            DB::beginTransaction();

            $batchId = (string) Str::orderedUuid();

            return tap($callback($batchId), function () use ($finished, $batchId) {
                if ($finished) {
                    $finished($batchId);
                }

                DB::commit();
            });
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }
}
