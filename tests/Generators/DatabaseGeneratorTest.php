<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Generators;

use Binaryk\LaravelRestify\Generators\DatabaseGenerator;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Carbon\Carbon;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;

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
    public function it_generates_an_email_for_a_string_column_named_like_an_email(): void
    {
        $value = DatabaseGenerator::make()->fake(new Column('email', Type::getType('string')));

        $this->assertIsString($value);
        $this->assertStringContainsString('@', $value);
    }

    #[Test]
    public function it_generates_a_hashed_secret_for_a_password_column(): void
    {
        $value = DatabaseGenerator::make()->fake(new Column('password', Type::getType('string')));

        $this->assertTrue(Hash::check('secret', $value));
    }

    #[Test]
    public function it_generates_an_ordered_uuid_for_a_uuid_column(): void
    {
        $value = DatabaseGenerator::make()->fake(new Column('uuid', Type::getType('string')));

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            (string) $value,
        );
    }

    #[Test]
    public function it_generates_a_generic_string_for_any_other_string_column(): void
    {
        $value = DatabaseGenerator::make()->fake(new Column('title', Type::getType('string')));

        $this->assertIsString($value);
        $this->assertNotSame('', $value);
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
    public function it_fakes_a_random_number_when_the_foreign_key_guesses_no_table(): void
    {
        $column = new Column('imaginary_id', Type::getType('integer'));
        $column->setAutoincrement(false);

        $this->assertIsInt(DatabaseGenerator::make()->fake($column));
    }

    #[Test]
    public function it_fakes_a_random_number_for_a_plain_integer_column(): void
    {
        $column = new Column('score', Type::getType('integer'));
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
