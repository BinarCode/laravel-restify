<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Mergeable;
use Binaryk\LaravelRestify\Repositories\Repository;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class PostMergeableRepository extends Repository implements Mergeable
{
    public static $model = Post::class;

    /**
     * Get the URI key for the resource.
     *
     * @return string
     */
    public static function uriKey()
    {
        return 'posts-mergeable';
    }

    /**
     * @param  RestifyRequest  $request
     * @return array
     */
    public function fields(RestifyRequest $request)
    {
        return [
            Field::new('user_id'),

            Field::new('title')->storingRules('required')->messages([
                'required' => 'This field is required',
            ]),
        ];
    }
}
