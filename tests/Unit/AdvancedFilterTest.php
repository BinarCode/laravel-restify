<?php

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Filters\AdvancedFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Testing\Fluent\AssertableJson;

class AdvancedFilterTest extends IntegrationTestCase
{
    public function test_advanced_filters_can_serialize(): void
    {
        $filter = new class extends AdvancedFilter
        {
            public static $uriKey = 'status-filter';

            public string $type = 'multiselect';

            public ?string $title = 'Status filter';

            public string $description = 'Short description';

            public ?string $column = 'status';

            public function rules(Request $request): array
            {
                return [
                    'status' => ['required'],
                ];
            }

            public function filter(RestifyRequest $request, Relation|Builder $query, $value)
            {
                return $query;
            }

            public function options(Request $request): array
            {
                return [
                    'draft' => 'Draft',
                ];
            }
        };

        tap(
            AssertableJson::fromArray($filter->jsonSerialize()),
            function (AssertableJson $json) {
                $json
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
                    ]]);
            }
        );
    }
}
