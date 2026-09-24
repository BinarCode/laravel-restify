<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Generators;

use Binaryk\LaravelRestify\Generators\DatabaseGenerator;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Carbon\Carbon;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class DatabaseGeneratorTest extends IntegrationTestCase
{
    #[Test]
    public function it_generates_html_for_a_text_column(): void
    {
        $value = DatabaseGenerator::make()->fake(new Column('body', Type::getType('text')));

        $this->assertIsString($value);
        $this->assertStringContainsString('<', $value);
    }

    #[Test]
    #[TestWith(['email', '/^[^@\s]+@[^@\s]+\.[^@\s]+$/'], 'an email column')]
    #[TestWith(['password', null], 'a password column')]
    #[TestWith(['uuid', '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/'], 'a uuid column')]
    #[TestWith(['image', '#^https?://#'], 'an image column')]
    #[TestWith(['picture', '#^https?://#'], 'a picture column')]
    #[TestWith(['avatar', '#^https?://#'], 'an avatar column')]
    #[TestWith(['title', '/^.+$/'], 'any other string column')]
    public function it_generates_the_right_kind_of_string_for_the_column_name(string $columnName, ?string $expectedPattern): void
    {
        $value = DatabaseGenerator::make()->fake(new Column($columnName, Type::getType('string')));

        if ($expectedPattern === null) {
            $this->assertTrue(Hash::check('secret', $value));

            return;
        }

        $this->assertMatchesRegularExpression($expectedPattern, (string) $value);
    }

    #[Test]
    public function it_generates_the_current_time_for_a_datetime_column(): void
    {
        $value = DatabaseGenerator::make()->fake(new Column('created_at', Type::getType('datetime')));

        $this->assertInstanceOf(Carbon::class, $value);
    }

    #[Test]
    public function it_generates_a_boolean_for_a_boolean_column(): void
    {
        $value = DatabaseGenerator::make()->fake(new Column('is_admin', Type::getType('boolean')));

        $this->assertIsBool($value);
    }

    #[Test]
    public function it_leaves_the_autoincrementing_primary_key_to_the_database(): void
    {
        $column = new Column('id', Type::getType('bigint'));
        $column->setAutoincrement(true);

        $this->assertNull(DatabaseGenerator::make()->fake($column));
    }

    #[Test]
    public function it_picks_an_existing_row_id_when_the_foreign_key_guesses_a_real_table(): void
    {
        $user = User::factory()->create();

        $column = new Column('user_id', Type::getType('integer'));
        $column->setAutoincrement(false);

        $this->assertSame($user->id, DatabaseGenerator::make()->fake($column));
    }

    #[Test]
    public function it_pluralizes_a_two_word_foreign_key_column_to_guess_its_table(): void
    {
        Schema::create('post_categories', function (Blueprint $table) {
            $table->id();
        });

        $id = DB::table('post_categories')->insertGetId([]);

        $column = new Column('post_category_id', Type::getType('integer'));
        $column->setAutoincrement(false);

        $this->assertSame($id, DatabaseGenerator::make()->fake($column));
    }

    #[Test]
    #[TestWith(['imaginary_id'], 'a foreign key that guesses no real table')]
    #[TestWith(['score'], 'a plain integer column')]
    public function it_fakes_a_random_number_for_an_integer_column(string $columnName): void
    {
        $column = new Column($columnName, Type::getType('integer'));
        $column->setAutoincrement(false);

        $this->assertIsInt(DatabaseGenerator::make()->fake($column));
    }

    #[Test]
    public function it_returns_null_for_a_column_type_it_does_not_know_how_to_fake(): void
    {
        $value = DatabaseGenerator::make()->fake(new Column('price', Type::getType('float')));

        $this->assertNull($value);
    }
}
