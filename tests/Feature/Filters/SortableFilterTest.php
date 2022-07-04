<?php

namespace Binaryk\LaravelRestify\Tests\Feature\Filters;

use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class SortableFilterTest extends IntegrationTest
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

        $this->assertSame('Alisa', $this->getJson('users?sort=name')
            ->json('data.0.attributes.name'));

        $this->assertSame('Zoro', $this->getJson('users?sort=name')
            ->json('data.1.attributes.name'));
        $this->assertSame('Zoro', $this->getJson('users?sort=-name')
            ->json('data.0.attributes.name'));
        $this->assertSame('Alisa', $this->getJson('users?sort=-name')
            ->json('data.1.attributes.name'));
    }
}
