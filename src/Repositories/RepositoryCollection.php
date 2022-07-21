<?php

namespace Binaryk\LaravelRestify\Repositories;

use Illuminate\Support\Arr;

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
            'prev' => array_key_exists('prev_page_url', $paginated) ? $paginated['prev_page_url'] :
                collect(collect($paginated['links'])->firstWhere('label', 'Previous'))->get('url')
                ?? null,
            'next' => array_key_exists('prev_page_url', $paginated) ? $paginated['prev_page_url'] :
                collect(collect($paginated['links'])->firstWhere('label', 'Previous'))->get('url')
                ?? null,
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
            'links',
            'first_page_url',
            'last_page_url',
            'prev_page_url',
            'next_page_url',
        ]);
    }
}
