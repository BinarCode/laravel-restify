<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class BookRepository extends Repository
{
    public static $model = Book::class;

    /**
     * Get the URI key for the resource.
     *
     * @return string
     */
    public static function uriKey()
    {
        return 'books';
    }

    /**
     * @param  RestifyRequest  $request
     * @return array
     */
    public function fields(RestifyRequest $request)
    {
        return [
            Field::make('title')->storingRules('required')->messages([
                'required' => 'This field is required',
            ]),
        ];
    }
}
