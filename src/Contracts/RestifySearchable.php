<?php

namespace Binaryk\LaravelRestify\Contracts;

use Illuminate\Http\Request;

/**
 * @package Binaryk\LaravelRestify\Contracts;
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
interface RestifySearchable
{
    const DEFAULT_PER_PAGE = 15;

    const MATCH_TEXT = 'text';
    const MATCH_BOOL = 'bool';
    const MATCH_INTEGER = 'integer';

    /**
     * @param  Request  $request
     * @param  array  $fields
     * @return array
     */
    public function serializeForIndex(Request $request, array $fields = []);

    /**
     * @return array
     */
    public function getSearchableFields();

    /**
     * @return array
     */
    public function getWiths();

    /**
     * @return array
     */
    public function getInFields();

    /**
     * Find matches in the table by given value
     * Returns an array like:
     * [ 'table_column_name' => 'type' ], type can be: text, bool, boolean, int, integer, number
     * e.g. [ 'id' => 'int' ]
     *
     * To use this filter we have to send in query:
     * [  'match' => [ 'id' => 1 ] ]
     * @return array
     */
    public function getMatchByFields();

    /**
     * @return array
     */
    public function getOrderByFields();
}
