<?php

namespace Binaryk\LaravelRestify\Contracts;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
interface RestifySearchable
{
    const DEFAULT_PER_PAGE = 15;

    const MATCH_TEXT = 'text';
    const MATCH_BOOL = 'bool';
    const MATCH_INTEGER = 'integer';

    /**
     * @param  RestifyRequest  $request
     * @param  array  $fields
     * @return array
     */
    public function serializeForIndex(RestifyRequest $request, array $fields = []);

    /**
     * @return array
     */
    public static function getSearchableFields();

    /**
     * @return array
     */
    public static function getWiths();

    /**
     * @return array
     */
    public static function getInFields();

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
