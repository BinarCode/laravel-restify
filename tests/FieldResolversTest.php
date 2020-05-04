<?php

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Tests\Fixtures\Book;
use Binaryk\LaravelRestify\Tests\Fixtures\BookRepository;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class FieldResolversTest extends IntegrationTest
{
    public function test_show_callback_change_details_value()
    {
        tap($this->basicField(), function (Field $field) {
            $book = factory(Book::class)->create();
            $repository = $this->basicRepository();
            $repository->repository = $book;

            $field->showCallback(function ($value, $repo) use ($book, $repository) {
                $this->assertInstanceOf(get_class($repository), $repo);
                $this->assertSame($value, $book->title); //assert that the value is read from the database
                return 'something else';
            });

            $this->assertSame($field->resolveForShow($repository), 'something else');
        });
    }

    public function basicField()
    {
        return Field::make('title');
    }

    public function basicRepository()
    {
        return new class extends BookRepository {
        };
    }
}
