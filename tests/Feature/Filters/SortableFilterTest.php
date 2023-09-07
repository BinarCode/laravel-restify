<?php

namespace Binaryk\LaravelRestify\Tests\Feature\Filters;

use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Filters\Sorts\NaturalSortFilter;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

class SortableFilterTest extends IntegrationTestCase
{
    public function test_can_order_using_filter_sortable_definition(): void
    {
        User::factory()->create([
            'name' => 'Zoro',
        ]);

        User::factory()->create([
            'name' => 'Alisa',
        ]);

        UserRepository::$sort = [
            'name' => SortableFilter::make()->setColumn('name'),
        ];

        $this->assertSame('Alisa', $this->getJson(UserRepository::route(query: ['sort' => 'name']))
            ->json('data.0.attributes.name'));

        $this->assertSame('Zoro', $this->getJson(UserRepository::route(query: ['sort' => 'name']))
            ->json('data.1.attributes.name'));
        $this->assertSame('Zoro', $this->getJson(UserRepository::route(query: ['sort' => '-name']))
            ->json('data.0.attributes.name'));
        $this->assertSame('Alisa', $this->getJson(UserRepository::route(query: ['sort' => '-name']))
            ->json('data.1.attributes.name'));
    }

    public function test_can_order_using_natural_sortable_filter(): void
    {
        User::factory()->create([
            'name' => '1',
        ]);

        User::factory()->create([
            'name' => '10',
        ]);

        User::factory()->create([
            'name' => '2',
        ]);

        User::factory()->create([
            'name' => '20',
        ]);

        UserRepository::$sort = [
            'name' => NaturalSortFilter::class,
        ];

        $this->assertSame('1', $this->getJson(UserRepository::route(query: ['sort' => 'name']))
            ->json('data.0.attributes.name'));

        $this->assertSame('2', $this->getJson(UserRepository::route(query: ['sort' => 'name']))
            ->json('data.1.attributes.name'));

        $this->assertSame('10', $this->getJson(UserRepository::route(query: ['sort' => 'name']))
            ->json('data.2.attributes.name'));

        $this->assertSame('20', $this->getJson(UserRepository::route(query: ['sort' => 'name']))
            ->json('data.3.attributes.name'));

        $this->assertSame('20', $this->getJson(UserRepository::route(query: ['sort' => '-name']))
            ->json('data.0.attributes.name'));

        $this->assertSame('10', $this->getJson(UserRepository::route(query: ['sort' => '-name']))
            ->json('data.1.attributes.name'));

        $this->assertSame('2', $this->getJson(UserRepository::route(query: ['sort' => '-name']))
            ->json('data.2.attributes.name'));

        $this->assertSame('1', $this->getJson(UserRepository::route(query: ['sort' => '-name']))
            ->json('data.3.attributes.name'));
    }
}
