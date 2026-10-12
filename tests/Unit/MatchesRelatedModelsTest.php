<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Binaryk\LaravelRestify\Traits\MatchesRelatedModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

class MatchesRelatedModelsTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('case_sensitive_labels', function (Blueprint $table): void {
            $table->string('code')->primary();
        });
    }

    #[Test]
    public function it_keeps_ids_that_differ_only_in_case_apart_when_the_database_does(): void
    {
        $lower = CaseSensitiveLabel::query()->create(['code' => 'abc']);
        $upper = CaseSensitiveLabel::query()->create(['code' => 'ABC']);

        $matched = $this->match(['ABC', 'abc']);

        $this->assertCount(2, $matched);
        $this->assertTrue($matched[0]->is($upper));
        $this->assertTrue($matched[1]->is($lower));
    }

    #[Test]
    public function it_does_not_match_an_id_the_database_does_not_return_because_of_its_case(): void
    {
        $lower = CaseSensitiveLabel::query()->create(['code' => 'abc']);

        $matched = $this->match(['abc', 'ABC']);

        $this->assertCount(1, $matched);
        $this->assertTrue($matched[0]->is($lower));
        $this->assertArrayNotHasKey(1, $matched);
    }

    /**
     * @param  array<int, string>  $ids
     * @return array<int, Model>
     */
    private function match(array $ids): array
    {
        $matcher = new class
        {
            use MatchesRelatedModels;

            /**
             * @param  array<int, string>  $ids
             * @return array<int, Model>
             */
            public function match(Model $model, array $ids): array
            {
                return $this->matchModelsToIds($model->newQuery(), $ids);
            }
        };

        return $matcher->match(new CaseSensitiveLabel, $ids);
    }
}

class CaseSensitiveLabel extends Model
{
    protected $table = 'case_sensitive_labels';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
