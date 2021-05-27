<?php

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Testing\Fluent\AssertableJson;

class MatchableFilterTest extends IntegrationTest
{
    public function test_matchable_filter_has_key(): void
    {
        $filter = new class extends MatchFilter {

        };

        tap(
            AssertableJson::fromArray($filter->jsonSerialize()),
            function (AssertableJson $json) {
                $json
                    ->where('key', 'matches')
                ;
            }
        );
    }
}
