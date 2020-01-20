<?php

namespace Binaryk\LaravelRestify\Repositories;

use Illuminate\Support\Arr;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RepositoryCollection
{
    /**
     * Get the pagination links for the response.
     *
     * @param  array  $paginated
     * @return array
     */
    public static function paginationLinks($paginated)
    {
        return [
            'first' => $paginated['first_page_url'] ?? null,
            'last' => $paginated['last_page_url'] ?? null,
            'prev' => $paginated['prev_page_url'] ?? null,
            'next' => $paginated['next_page_url'] ?? null,
        ];
    }

    /**
     * Gather the meta data for the response.
     *
     * @param  array  $paginated
     * @return array
     */
    public static function meta($paginated)
    {
        return Arr::except($paginated, [
            'data',
            'first_page_url',
            'last_page_url',
            'prev_page_url',
            'next_page_url',
        ]);
    }
}
