<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Fields\Field;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

final class HiddenModelAttributes
{
    /** @var array<string, true> */
    private static array $warned = [];

    public static function hidesField(Model $model, Field $field): bool
    {
        $attribute = $field->attribute;

        if (! is_string($attribute)) {
            return false;
        }

        if (in_array($attribute, $model->getHidden(), true)) {
            return true;
        }

        $visible = $model->getVisible();

        if ($visible === [] || $field->computed()) {
            return false;
        }

        return ! in_array($attribute, $visible, true);
    }

    /**
     * @param  iterable<mixed>  $fields
     */
    public static function warnAboutSerializedFields(Repository $repository, iterable $fields): void
    {
        if ($repository instanceof Mergeable || ! config('app.debug')) {
            return;
        }

        foreach ($fields as $field) {
            if (! $field instanceof Field || ! is_string($field->attribute) || ! self::hidesField($repository->resource, $field)) {
                continue;
            }

            $repositoryClass = $repository::class;
            $warningKey = "{$repositoryClass}.{$field->attribute}";

            if (isset(self::$warned[$warningKey])) {
                continue;
            }

            self::$warned[$warningKey] = true;

            $modelClass = $repository->resource::class;

            Log::warning("Restify repository [{$repositoryClass}] serializes the [{$field->attribute}] attribute, which [{$modelClass}] hides. Hide the field from show and index, or remove it.");
        }
    }

    public static function flushWarnings(): void
    {
        self::$warned = [];
    }
}
