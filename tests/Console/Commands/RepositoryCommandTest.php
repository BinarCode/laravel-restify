<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithGeneratedApp;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

class RepositoryCommandTest extends IntegrationTestCase
{
    use InteractsWithGeneratedApp;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('restify_departments', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('restify_employees', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->text('bio')->nullable();
            $table->integer('age')->nullable();
            $table->boolean('active')->default(true);
            $table->date('birth_date')->nullable();
            $table->dateTime('hired_at')->nullable();
            $table->decimal('salary')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('restify_department_id');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('restify_employees');
        Schema::dropIfExists('restify_departments');

        parent::tearDown();
    }

    #[Test]
    public function it_generates_a_repository_for_an_auto_detected_model(): void
    {
        $this->artisan('restify:repository', ['name' => 'RestifyEmployee', '--no-fields' => true])
            ->expectsOutputToContain('created successfully')
            ->assertExitCode(0);

        $path = $this->generatedAppPath.'/Restify/RestifyEmployeeRepository.php';
        $this->assertFileExists($path);

        $content = File::get($path);
        $this->assertStringContainsString('namespace App\Restify;', $content);
        $this->assertStringContainsString('use App\Models\RestifyEmployee;', $content);
        $this->assertStringContainsString('class RestifyEmployeeRepository extends Repository', $content);
        $this->assertStringContainsString('public static string $model = RestifyEmployee::class;', $content);
        $this->assertStringContainsString('id(),', $content);

        // The hidden base "Repository" class is generated alongside it.
        $this->assertFileExists($this->generatedAppPath.'/Restify/Repository.php');
    }

    #[Test]
    public function it_does_not_double_append_the_repository_suffix(): void
    {
        $this->artisan('restify:repository', ['name' => 'RestifyEmployeeRepository', '--no-fields' => true])
            ->assertExitCode(0);

        $this->assertFileExists($this->generatedAppPath.'/Restify/RestifyEmployeeRepository.php');
    }

    #[Test]
    public function it_cancels_when_declining_to_override_an_existing_repository(): void
    {
        $path = $this->generatedAppPath.'/Restify/RestifyEmployeeRepository.php';
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'original content');

        $this->artisan('restify:repository', ['name' => 'RestifyEmployee', '--no-fields' => true])
            ->expectsOutputToContain('Repository already exists at:')
            ->expectsConfirmation('Do you want to override it?', 'no')
            ->expectsOutputToContain('Repository creation cancelled.')
            ->assertExitCode(0);

        $this->assertSame('original content', File::get($path));
    }

    #[Test]
    public function it_overwrites_an_existing_repository_when_override_is_confirmed(): void
    {
        $path = $this->generatedAppPath.'/Restify/RestifyEmployeeRepository.php';
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'original content');

        $this->artisan('restify:repository', ['name' => 'RestifyEmployee', '--no-fields' => true])
            ->expectsConfirmation('Do you want to override it?', 'yes')
            ->assertExitCode(0);

        $this->assertStringContainsString('class RestifyEmployeeRepository extends Repository', File::get($path));
    }

    #[Test]
    public function it_overwrites_an_existing_repository_with_the_force_flag_without_prompting(): void
    {
        $path = $this->generatedAppPath.'/Restify/RestifyEmployeeRepository.php';
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'original content');

        $this->artisan('restify:repository', ['name' => 'RestifyEmployee', '--no-fields' => true, '--force' => true])
            ->expectsOutputToContain('created successfully')
            ->assertExitCode(0);

        $this->assertStringContainsString('class RestifyEmployeeRepository extends Repository', File::get($path));
    }

    #[Test]
    public function it_rejects_a_reserved_php_name(): void
    {
        $this->artisan('restify:repository', ['name' => 'class'])
            ->expectsOutputToContain('reserved by PHP')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_generates_fields_and_a_belongs_to_relationship_from_the_database_schema(): void
    {
        $this->artisan('restify:repository', ['name' => 'RestifyEmployee'])
            ->assertExitCode(0);

        $content = File::get($this->generatedAppPath.'/Restify/RestifyEmployeeRepository.php');

        // Plain columns become required fields, typed by their schema column type.
        $this->assertStringContainsString("field('name')->required(),", $content);
        $this->assertStringContainsString("field('email')->email()->required(),", $content);
        $this->assertStringContainsString("field('bio')->textarea()->required(),", $content);
        $this->assertStringContainsString("field('age')->number()->required(),", $content);
        $this->assertStringContainsString("field('active')->boolean()->required(),", $content);
        $this->assertStringContainsString("field('birth_date')->date()->required(),", $content);
        $this->assertStringContainsString("field('hired_at')->datetime()->required(),", $content);
        $this->assertStringContainsString("field('created_at')->datetime()->readonly(),", $content);
        $this->assertStringContainsString("field('updated_at')->datetime()->readonly(),", $content);

        // The FK column is not listed as a plain field...
        $this->assertStringNotContainsString("field('restify_department_id')", $content);

        // ...it becomes a BelongsTo relationship instead.
        $this->assertStringContainsString('use Binaryk\LaravelRestify\Fields\BelongsTo;', $content);
        $this->assertStringContainsString('public static function include(): array', $content);
        $this->assertStringContainsString("BelongsTo::make('restifyDepartment'),", $content);
    }

    #[Test]
    public function it_generates_a_has_many_relationship_for_the_inverse_side(): void
    {
        $this->artisan('restify:repository', ['name' => 'RestifyDepartment'])
            ->assertExitCode(0);

        $content = File::get($this->generatedAppPath.'/Restify/RestifyDepartmentRepository.php');

        $this->assertStringContainsString('use Binaryk\LaravelRestify\Fields\HasMany;', $content);
        $this->assertStringContainsString('public static function include(): array', $content);
        $this->assertStringContainsString("HasMany::make('restifyEmployees'),", $content);
    }

    #[Test]
    public function it_resolves_the_model_interactively_when_it_is_outside_the_default_location(): void
    {
        $this->artisan('restify:repository', ['name' => 'UserProfile', '--no-fields' => true])
            ->expectsOutputToContain('Model not found.')
            ->expectsQuestion('Please enter the model name (without namespace, e.g., User, Employee)', 'User')
            ->expectsOutputToContain('Using model: App\User')
            ->assertExitCode(0);

        $content = File::get($this->generatedAppPath.'/Restify/UserProfileRepository.php');
        $this->assertStringContainsString('use App\User;', $content);
        $this->assertStringContainsString('public static string $model = User::class;', $content);
    }

    #[Test]
    public function it_follows_an_existing_grouped_by_model_repository_pattern(): void
    {
        $existing = $this->generatedAppPath.'/Restify/Employees/EmployeeRepository.php';
        File::ensureDirectoryExists(dirname($existing));
        File::put($existing, "<?php\n\nnamespace App\\Restify\\Employees;\n\nclass EmployeeRepository {}\n");

        $this->artisan('restify:repository', ['name' => 'RestifyDepartment', '--no-fields' => true])
            ->expectsOutputToContain('Detected repository pattern: grouped-by-model')
            ->assertExitCode(0);

        $path = $this->generatedAppPath.'/Restify/Employees/RestifyDepartments/RestifyDepartmentRepository.php';
        $this->assertFileExists($path);
        $this->assertStringContainsString('namespace App\Restify\Employees\RestifyDepartments;', File::get($path));
    }

    #[Test]
    public function it_builds_the_policy_and_migration_with_the_all_option(): void
    {
        $this->artisan('restify:repository', [
            'name' => 'RestifyEmployee',
            '--all' => true,
            '--no-fields' => true,
        ])
            ->expectsConfirmation('Do you want to generate the migration [create_restify_employees_table]?', 'yes')
            ->assertExitCode(0);

        $this->assertFileExists($this->generatedAppPath.'/Policies/RestifyEmployeePolicy.php');

        $migrations = File::glob($this->generatedDatabasePath.'/migrations/*_create_restify_employees_table.php');
        $this->assertNotEmpty($migrations);
    }
}
