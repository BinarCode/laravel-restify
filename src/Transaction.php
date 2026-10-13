<?php

namespace Binaryk\LaravelRestify;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class Transaction
{
    public static function run(callable $callback, ?callable $finished = null, ?ConnectionInterface $connection = null)
    {
        $connection ??= DB::connection();

        try {
            $connection->beginTransaction();

            $batchId = (string) Str::orderedUuid();

            return tap($callback($batchId), function () use ($finished, $batchId, $connection) {
                if ($finished) {
                    $finished($batchId);
                }

                $connection->commit();
            });
        } catch (Throwable $e) {
            $connection->rollBack();

            throw $e;
        }
    }
}
