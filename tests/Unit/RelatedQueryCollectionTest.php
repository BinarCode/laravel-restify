<?php

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Filters\RelatedDto;
use Binaryk\LaravelRestify\Filters\RelatedQuery;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class RelatedQueryCollectionTest extends IntegrationTest
{
    public function test_can_create_collection_from_query(): void
    {
        $request = new RestifyRequest([
            'include' => 'users[email|name].posts[title].tags[id], users.comments[comment], buildings[title], creator',
        ]);

        $relatedDto = app(RelatedDto::class)->makeFromRequest($request);

        $relatedCollection = $relatedDto->related;

        $this->assertCount(3, $relatedCollection);

        /**
         * @var RelatedQuery $usesRelated
         */
        $usesRelated = $relatedCollection->first();

        $this->assertSame('users', $usesRelated->relation);
        $this->assertSame(['email', 'name'], $usesRelated->columns);
        $this->assertCount(2, $usesRelated->nested);
        $this->assertSame('posts', $usesRelated->nested->first()->relation);
        $this->assertSame('comments', $usesRelated->nested->last()->relation);

        /**
         * @var RelatedQuery $postsNested
         */
        $postsNested = $usesRelated->nested->first();
        $this->assertSame('posts', $postsNested->relation);
        $this->assertSame(['title'], $postsNested->columns);
        $this->assertCount(1, $postsNested->nested);

        /**
         * @var RelatedQuery $userPostTagsRelated
         */
        $userPostTagsRelated = $postsNested->nested->first();
        $this->assertSame('tags', $userPostTagsRelated->relation);
        $this->assertSame(['id'], $userPostTagsRelated->columns);
        $this->assertCount(0, $userPostTagsRelated->nested);

        /**
         * @var RelatedQuery $userCommentsNested
         */
        $userCommentsNested = $usesRelated->nested->last();
        $this->assertSame('comments', $userCommentsNested->relation);
        $this->assertSame(['comment'], $userCommentsNested->columns);
        $this->assertCount(0, $userCommentsNested->nested);

        /**
         * @var RelatedQuery $tagsNested
         */
        $tagsNested = $postsNested->nested->first();
        $this->assertSame('tags', $tagsNested->relation);
        $this->assertSame(['id'], $tagsNested->columns);
        $this->assertCount(0, $tagsNested->nested);

        /**
         * @var RelatedQuery $buildingsRelated
         */
        $buildingsRelated = $relatedCollection->get(1);

        $this->assertSame('buildings', $buildingsRelated->relation);
        $this->assertSame(['title'], $buildingsRelated->columns);
        $this->assertCount(0, $buildingsRelated->nested);

        /**
         * @var RelatedQuery $creatorRelated
         */
        $creatorRelated = $relatedCollection->get(2);

        $this->assertSame('creator', $creatorRelated->relation);
        $this->assertEmpty($creatorRelated->columns);
        $this->assertCount(0, $creatorRelated->nested);

        $this->assertCount(2, $relatedDto->getNestedFor('users'));
        $this->assertSame(['email', 'name'], $relatedDto->getColumnsFor('users'));
        $this->assertSame(['title'], $relatedDto->getColumnsFor('buildings'));
        $this->assertSame('*', $relatedDto->getColumnsFor('creator'));
    }
}
