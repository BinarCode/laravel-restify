<?php

namespace Binaryk\LaravelRestify\Contracts;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
interface RestifySearchable
{
    public const DEFAULT_PER_PAGE = 15;

    public const DEFAULT_RELATABLE_PER_PAGE = 15;

    public const MATCH_TEXT = 'text';

    public const MATCH_BOOL = 'bool';

    public const MATCH_INTEGER = 'integer';

    public const MATCH_DATETIME = 'datetime';

    public const MATCH_BETWEEN = 'between';

    public const MATCH_ARRAY = 'array';

    /**
     * Get available attributes for query params search.
     */
    public static function searchables(): array;

    /**
     * Get relations available for query params.
     */
    public static function withs(): array;

    /**
     * Find matches in the table by given value
     * Returns an array like:
     * [ 'table_column_name' => 'type' ], type can be: text, bool, boolean, int, integer, number
     * e.g. [ 'id' => 'int' ].
     *
     * To use this filter we have to send in query:
     * [  'match' => [ 'id' => 1 ] ]
     */
    public static function matches(): array;

    public static function sorts(): array;
}
