<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class ComputedFieldKeyNumberingTest extends IntegrationTestCase
{
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();

        $this->post = $this->mockPost();
    }

    #[Test]
    #[TestWith([
        [['computed'], ['field', 'Computed'], ['computed'], ['computed'], ['field', 'Computed_3'], ['field', 'title'], ['computed']],
        [0 => 'Computed_1', 1 => 'Computed', 2 => 'Computed_2', 3 => 'Computed_4', 4 => 'Computed_3', 5 => 'title', 6 => 'Computed_5'],
        'index',
    ], 'S1 index')]
    #[TestWith([
        [['computed'], ['field', 'Computed'], ['computed'], ['computed'], ['field', 'Computed_3'], ['field', 'title'], ['computed']],
        [0 => 'Computed_1', 1 => 'Computed', 2 => 'Computed_2', 3 => 'Computed_4', 4 => 'Computed_3', 5 => 'title', 6 => 'Computed_5'],
        'show',
    ], 'S1 show')]
    #[TestWith([
        [['field', 'Computed_10'], ['computed'], ['computed'], ['field', 'title'], ['computed'], ['field', 'Computed_1'], ['computed']],
        [0 => 'Computed_10', 1 => 'Computed', 2 => 'Computed_2', 3 => 'title', 4 => 'Computed_3', 5 => 'Computed_1', 6 => 'Computed_4'],
        'index',
    ], 'S2 index')]
    #[TestWith([
        [['field', 'Computed_10'], ['computed'], ['computed'], ['field', 'title'], ['computed'], ['field', 'Computed_1'], ['computed']],
        [0 => 'Computed_10', 1 => 'Computed', 2 => 'Computed_2', 3 => 'title', 4 => 'Computed_3', 5 => 'Computed_1', 6 => 'Computed_4'],
        'show',
    ], 'S2 show')]
    #[TestWith([
        [['computed'], ['computed'], ['computed']],
        [0 => 'Computed', 1 => 'Computed_1', 2 => 'Computed_2'],
        'index',
    ], 'S3 index')]
    #[TestWith([
        [['computed'], ['computed'], ['computed']],
        [0 => 'Computed', 1 => 'Computed_1', 2 => 'Computed_2'],
        'show',
    ], 'S3 show')]
    #[TestWith([
        [['field-labeled', 'title', 'Computed_2'], ['computed'], ['computed'], ['computed']],
        [0 => 'Computed_2', 1 => 'Computed', 2 => 'Computed_1', 3 => 'Computed_3'],
        'index',
    ], 'S4 index')]
    #[TestWith([
        [['field-labeled', 'title', 'Computed_2'], ['computed'], ['computed'], ['computed']],
        [0 => 'Computed_2', 1 => 'Computed', 2 => 'Computed_1', 3 => 'Computed_3'],
        'show',
    ], 'S4 show')]
    #[TestWith([
        [['field-labeled', 'Computed_1', 'x'], ['computed'], ['computed']],
        [0 => 'x', 1 => 'Computed', 2 => 'Computed_2'],
        'index',
    ], 'attribute reservation index')]
    #[TestWith([
        [['field-labeled', 'Computed_1', 'x'], ['computed'], ['computed']],
        [0 => 'x', 1 => 'Computed', 2 => 'Computed_2'],
        'show',
    ], 'attribute reservation show')]
    #[TestWith([
        [['field', 'Computed_0'], ['field', 'Computed_02'], ['field', 'computed_1'], ['computed'], ['computed']],
        [0 => 'Computed_0', 1 => 'Computed_02', 2 => 'computed_1', 3 => 'Computed', 4 => 'Computed_1'],
        'index',
    ], 'S5 index')]
    #[TestWith([
        [['field', 'Computed_0'], ['field', 'Computed_02'], ['field', 'computed_1'], ['computed'], ['computed']],
        [0 => 'Computed_0', 1 => 'Computed_02', 2 => 'computed_1', 3 => 'Computed', 4 => 'Computed_1'],
        'show',
    ], 'S5 show')]
    #[TestWith([
        [['computed-labeled', 'Computed_1'], ['computed'], ['computed']],
        [0 => 'Computed_1', 1 => 'Computed', 2 => 'Computed_2'],
        'index',
    ], 'S6 index')]
    #[TestWith([
        [['computed-labeled', 'Computed_1'], ['computed'], ['computed']],
        [0 => 'Computed_1', 1 => 'Computed', 2 => 'Computed_2'],
        'show',
    ], 'S6 show')]
    #[TestWith([
        [
            ['computed'], ['computed'], ['field', 'Computed_2'],
            ['computed'], ['computed'], ['computed'], ['field', 'Computed_5'],
            ['computed'], ['computed'], ['computed'], ['computed'], ['field', 'Computed_11'],
            ['computed'], ['computed'], ['computed'],
        ],
        [
            0 => 'Computed', 1 => 'Computed_1', 2 => 'Computed_2',
            3 => 'Computed_3', 4 => 'Computed_4', 5 => 'Computed_6', 6 => 'Computed_5',
            7 => 'Computed_7', 8 => 'Computed_8', 9 => 'Computed_9', 10 => 'Computed_10', 11 => 'Computed_11',
            12 => 'Computed_12', 13 => 'Computed_13', 14 => 'Computed_14',
        ],
        'index',
    ], 'S8 index')]
    #[TestWith([
        [
            ['computed'], ['computed'], ['field', 'Computed_2'],
            ['computed'], ['computed'], ['computed'], ['field', 'Computed_5'],
            ['computed'], ['computed'], ['computed'], ['computed'], ['field', 'Computed_11'],
            ['computed'], ['computed'], ['computed'],
        ],
        [
            0 => 'Computed', 1 => 'Computed_1', 2 => 'Computed_2',
            3 => 'Computed_3', 4 => 'Computed_4', 5 => 'Computed_6', 6 => 'Computed_5',
            7 => 'Computed_7', 8 => 'Computed_8', 9 => 'Computed_9', 10 => 'Computed_10', 11 => 'Computed_11',
            12 => 'Computed_12', 13 => 'Computed_13', 14 => 'Computed_14',
        ],
        'show',
    ], 'S8 show')]
    public function unlabeled_computed_fields_get_positional_keys(array $spec, array $expectedKeyByIndex, string $endpoint): void
    {
        PostRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn($this->fieldsFromSpec($spec));

        $response = $endpoint === 'index'
            ? $this->getJson(PostRepository::route())
            : $this->getJson(PostRepository::route($this->post));

        $response->assertOk();

        $attributes = $endpoint === 'index'
            ? $response->json('data.0.attributes')
            : $response->json('data.attributes');

        $expectedAttributes = [];

        foreach ($spec as $index => $entry) {
            if (! array_key_exists($index, $expectedKeyByIndex)) {
                continue;
            }

            $expectedAttributes[$expectedKeyByIndex[$index]] = $this->expectedValueFor($spec, $index);
        }

        $this->assertSame($expectedAttributes, $attributes);
    }

    #[Test]
    public function s7_hidden_computed_fields_still_reserve_their_position_and_stay_stable_across_index_and_show(): void
    {
        PostRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                field(fn () => 'computed-0')->canSee(fn () => false),
                field(fn () => 'computed-1'),
            ]);

        $indexAttributes = $this->getJson(PostRepository::route())
            ->assertOk()
            ->json('data.0.attributes');

        $showAttributes = $this->getJson(PostRepository::route($this->post))
            ->assertOk()
            ->json('data.attributes');

        $this->assertArrayNotHasKey('Computed', $indexAttributes);
        $this->assertSame('computed-1', $indexAttributes['Computed_1']);

        $this->assertArrayNotHasKey('Computed', $showAttributes);
        $this->assertSame('computed-1', $showAttributes['Computed_1']);
    }

    #[Test]
    public function s9_store_and_update_never_fill_computed_keys_from_the_payload(): void
    {
        PostRepository::partialMock()
            ->shouldReceive('fieldsForStore')
            ->andReturn([
                Field::new('title'),
                field(fn () => 'first computed value'),
                field(fn () => 'second computed value'),
            ]);

        $this->postJson(PostRepository::route(), [
            'title' => 'Some post title',
            'Computed' => 'attacker value',
            'Computed_1' => 'attacker value',
        ])
            ->assertCreated()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('data.attributes.Computed', 'first computed value')
                ->where('data.attributes.Computed_1', 'second computed value')
                ->etc());

        $this->assertDatabaseHas(Post::class, [
            'title' => 'Some post title',
        ]);

        PostRepository::partialMock()
            ->shouldReceive('fieldsForUpdate')
            ->andReturn([
                Field::new('title'),
                field(fn () => 'updated first computed value'),
                field(fn () => 'updated second computed value'),
            ]);

        $this->putJson(PostRepository::route($this->post), [
            'title' => 'Updated title',
            'Computed' => 'attacker value',
            'Computed_1' => 'attacker value',
        ])
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('data.attributes.Computed', 'updated first computed value')
                ->where('data.attributes.Computed_1', 'updated second computed value')
                ->etc());

        $this->assertDatabaseHas(Post::class, [
            'id' => $this->post->id,
            'title' => 'Updated title',
        ]);
    }

    #[Test]
    #[TestWith([1])]
    #[TestWith([2])]
    #[TestWith([3])]
    #[TestWith([4])]
    #[TestWith([5])]
    #[TestWith([6])]
    #[TestWith([7])]
    #[TestWith([8])]
    #[TestWith([9])]
    #[TestWith([10])]
    #[TestWith([11])]
    #[TestWith([12])]
    #[TestWith([13])]
    #[TestWith([14])]
    #[TestWith([15])]
    #[TestWith([16])]
    #[TestWith([17])]
    #[TestWith([18])]
    #[TestWith([19])]
    #[TestWith([20])]
    #[TestWith([21])]
    #[TestWith([22])]
    #[TestWith([23])]
    #[TestWith([24])]
    #[TestWith([25])]
    #[TestWith([26])]
    #[TestWith([27])]
    #[TestWith([28])]
    #[TestWith([29])]
    #[TestWith([30])]
    #[TestWith([31])]
    #[TestWith([32])]
    #[TestWith([33])]
    #[TestWith([34])]
    #[TestWith([35])]
    #[TestWith([36])]
    #[TestWith([37])]
    #[TestWith([38])]
    #[TestWith([39])]
    #[TestWith([40])]
    #[TestWith([41])]
    #[TestWith([42])]
    #[TestWith([43])]
    #[TestWith([44])]
    #[TestWith([45])]
    #[TestWith([46])]
    #[TestWith([47])]
    #[TestWith([48])]
    #[TestWith([49])]
    #[TestWith([50])]
    public function s10_random_field_mixes_keep_keys_unique_stable_and_positional(int $seed): void
    {
        $spec = $this->randomSpecFor($seed);

        PostRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn($this->fieldsFromSpec($spec));

        $indexAttributes = $this->getJson(PostRepository::route())
            ->assertOk()
            ->json('data.0.attributes');

        $showAttributes = $this->getJson(PostRepository::route($this->post))
            ->assertOk()
            ->json('data.attributes');

        $this->assertComputedKeyProperties($spec, $indexAttributes);
        $this->assertComputedKeyProperties($spec, $showAttributes);
    }

    /**
     * @param  list<array{0: string, 1?: string, 2?: string}>  $spec
     * @return list<Field>
     */
    private function fieldsFromSpec(array $spec): array
    {
        $fields = [];

        foreach ($spec as $index => $entry) {
            $fields[] = match ($entry[0]) {
                'computed' => field(fn () => "computed-{$index}"),
                'computed-hidden' => field(fn () => "computed-{$index}")->canSee(fn () => false),
                'computed-labeled' => field(fn () => "computed-{$index}")->label($entry[1]),
                'field' => field($entry[1], fn () => $entry[1]),
                'field-labeled' => field($entry[1], fn () => $entry[1])->label($entry[2]),
            };
        }

        return $fields;
    }

    /**
     * @param  list<array{0: string, 1?: string, 2?: string}>  $spec
     */
    private function expectedValueFor(array $spec, int $index): string
    {
        $entry = $spec[$index];

        return match ($entry[0]) {
            'computed', 'computed-hidden', 'computed-labeled' => "computed-{$index}",
            'field', 'field-labeled' => $entry[1],
        };
    }

    /**
     * Random, mixed, out-of-order scenarios: unlabeled and hidden computed fields, real
     * attributes (some named or labeled like reserved keys, including non-reserved lookalikes),
     * and attributes whose label differs from their attribute.
     *
     * @return list<array{0: string, 1?: string, 2?: string}>
     */
    private function randomSpecFor(int $seed): array
    {
        mt_srand($seed);

        $count = mt_rand(6, 14);

        $lookalikes = ['Computed_0', 'Computed_02', 'computed_1'];
        shuffle($lookalikes);

        $spec = [];

        for ($index = 0; $index < $count; $index++) {
            $roll = mt_rand(1, 8);

            if ($roll === 1) {
                $spec[] = ['computed'];
            } elseif ($roll === 2) {
                $spec[] = ['computed-hidden'];
            } elseif ($roll === 3) {
                $spec[] = ['field', "attr_{$index}"];
            } elseif ($roll === 4) {
                $spec[] = ['field', $index === 0 ? 'Computed' : "Computed_{$index}"];
            } elseif ($roll === 5) {
                $spec[] = ['computed-labeled', $index === 0 ? 'Computed' : "Computed_{$index}"];
            } elseif ($roll === 6) {
                $spec[] = ['field-labeled', "attr_{$index}", "label_{$index}"];
            } elseif ($roll === 7) {
                $spec[] = ['field-labeled', "attr_{$index}", 'Computed_'.(100 + $index)];
            } elseif ($lookalikes !== []) {
                $spec[] = ['field', array_pop($lookalikes)];
            } else {
                $spec[] = ['field', "attr_{$index}"];
            }
        }

        return $spec;
    }

    /**
     * Asserts the numbering properties directly, without recomputing the expected key map:
     * (a) every visible field resolves to its own key - none collapsed onto another;
     * (b) every explicit field (named, labeled, or a labeled computed field) keeps its own
     *     key and value; (c) the unlabeled ("auto") computed fields' numbers rise strictly in
     *     declaration order, none lands on a reserved number, and every number below the
     *     highest one used is either used or reserved - accounting for hidden computed fields,
     *     which still consume a slot but never appear in the response.
     *
     * @param  list<array{0: string, 1?: string, 2?: string}>  $spec
     * @param  array<string, mixed>  $attributes
     */
    private function assertComputedKeyProperties(array $spec, array $attributes): void
    {
        $expectedVisibleCount = 0;

        foreach ($spec as $entry) {
            if ($entry[0] !== 'computed-hidden') {
                $expectedVisibleCount++;
            }
        }

        $this->assertCount($expectedVisibleCount, $attributes, 'every visible field must resolve to its own key, with none overwritten');

        $reserved = $this->reservedNumbersFor($spec);
        $hiddenAutoCount = 0;
        $autoNumbers = [];

        foreach ($spec as $index => $entry) {
            if ($entry[0] === 'computed-hidden') {
                $hiddenAutoCount++;

                continue;
            }

            if ($entry[0] === 'computed') {
                $autoNumbers[] = $this->numberAssignedTo($this->expectedValueFor($spec, $index), $attributes);

                continue;
            }

            $expectedKey = match ($entry[0]) {
                'field' => $entry[1],
                'field-labeled' => $entry[2],
                'computed-labeled' => $entry[1],
            };

            $this->assertArrayHasKey($expectedKey, $attributes, "explicit field at index {$index} lost its key [{$expectedKey}]");
            $this->assertSame($this->expectedValueFor($spec, $index), $attributes[$expectedKey], "explicit field at index {$index} lost its value");
        }

        sort($autoNumbers);

        $previous = -1;

        foreach ($autoNumbers as $number) {
            $this->assertGreaterThan($previous, $number, 'auto-assigned numbers must strictly increase in declaration order');
            $this->assertNotContains($number, $reserved, "auto-assigned number [{$number}] collides with a reserved key");
            $previous = $number;
        }

        if ($autoNumbers === []) {
            return;
        }

        $max = max($autoNumbers);
        $usedBelowMax = count(array_unique(array_merge(
            $autoNumbers,
            array_filter($reserved, fn (int $number): bool => $number <= $max)
        )));

        $this->assertLessThanOrEqual($hiddenAutoCount, ($max + 1) - $usedBelowMax, 'every gap below the highest used number must be explained by a hidden field');
    }

    private function numberAssignedTo(string $value, array $attributes): int
    {
        $key = array_search($value, $attributes, true);

        $this->assertNotFalse($key, "no attribute key resolved to marker value [{$value}]");
        $this->assertMatchesRegularExpression('/^Computed(?:_[1-9]\d*)?$/', (string) $key, "auto-assigned key [{$key}] must follow the Computed/Computed_N pattern");

        return $key === 'Computed' ? 0 : (int) substr((string) $key, strlen('Computed_'));
    }

    /**
     * @param  list<array{0: string, 1?: string, 2?: string}>  $spec
     * @return list<int>
     */
    private function reservedNumbersFor(array $spec): array
    {
        $reserved = [];

        foreach ($spec as $entry) {
            $candidate = match ($entry[0]) {
                'field' => $entry[1],
                'field-labeled' => $entry[2],
                'computed-labeled' => $entry[1],
                default => null,
            };

            if ($candidate !== null && preg_match('/^Computed(?:_([1-9]\d*))?$/', $candidate, $matches) === 1) {
                $reserved[] = isset($matches[1]) ? (int) $matches[1] : 0;
            }
        }

        return $reserved;
    }
}
