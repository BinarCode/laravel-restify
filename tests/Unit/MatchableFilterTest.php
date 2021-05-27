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
                    ->dd()
                    ->dump()
                    ->where('type', 'multiselect')
                    ->where('advanced', true)
                    ->where('title', 'Status filter')
                    ->where('description', 'Short description')
                    ->where('column', 'status')
                    ->where('key', 'status-filter')
                    ->where('rules', [
                        'status' => ['required'],
                    ])
                    ->where('options', [[
                        'label' => 'Draft',
                        'property' => 'draft',
                    ]])
                ;
            }
        );
    }
}
