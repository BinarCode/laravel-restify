<?php

namespace Binaryk\LaravelRestify;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class Transaction
{
    public static function run(callable $callback, callable $finished = null)
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
