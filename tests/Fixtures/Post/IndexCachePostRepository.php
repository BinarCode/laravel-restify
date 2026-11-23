<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Fields\HasMany;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Tests\Fixtures\Comment\CommentRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * A posts repository that uses every input an index reads: match filters, search, sorts, relationships, an
 * advanced filter, grouping, and a query parameter of its own (`in_category`) read in indexQuery(). The MCP
 * index serializes fewer fields than the HTTP index, and of the relationships only `editor`: the other two
 * point to repositories that are not exposed as MCP tools.
 */
class IndexCachePostRepository extends Repository
{
    use HasMcpTools;

    public static string $model = Post::class;

    public static string $uriKey = 'index-cache-posts';

    public static array $search = ['title'];

    public static array $match = [
        'title' => 'string',
        'is_active' => 'bool',
    ];

    public static array $sort = ['title'];

    public static array $groupBy = ['category'];

    public static function include(): array
    {
        return [
            'user' => BelongsTo::make('user', UserRepository::class),
            'comments' => HasMany::make('comments', CommentRepository::class),
            'editor',
        ];
    }

    /**
     * @param  Builder<Post>|Relation<Post, Post, mixed>  $query
     */
    public static function indexQuery(RestifyRequest $request, Builder|Relation $query): void
    {
        $category = $request->input('in_category');

        if (is_string($category)) {
            $query->where('category', $category);
        }
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),
            field('description'),
            field('category'),
            field('is_active'),
        ];
    }

    public function fieldsForMcpIndex(RestifyRequest $request): array
    {
        return [
            field('title'),
        ];
    }

    public function filters(RestifyRequest $request): array
    {
        return [
            ValueFilter::new(),
        ];
    }
}
