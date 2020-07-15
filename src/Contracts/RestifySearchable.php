<?php

namespace Binaryk\LaravelRestify\Contracts;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
interface RestifySearchable
{
    const DEFAULT_PER_PAGE = 15;
    const DEFAULT_RELATABLE_PER_PAGE = 15;

    const MATCH_TEXT = 'text';
    const MATCH_BOOL = 'bool';
    const MATCH_INTEGER = 'integer';
    const MATCH_ARRAY = 'array';
    const MATCH_DATE = 'date';

    /**
     * Get available attributes for query params search.
     *
     * @return array
     */
    public static function getSearchableFields();

    /**
     * Get relations available for query params.
     *
     * @return array
     */
    public static function getWiths();

    /**
     * Find matches in the table by given value
     * Returns an array like:
     * [ 'table_column_name' => 'type' ], type can be: text, bool, boolean, int, integer, number
     * e.g. [ 'id' => 'int' ].
     *
     * To use this filter we have to send in query:
     * [  'match' => [ 'id' => 1 ] ]
     * @return array
     */
    public static function getMatchByFields();

    /**
     * @return array
     */
    public static function getOrderByFields();
}
