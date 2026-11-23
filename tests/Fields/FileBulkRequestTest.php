<?php

namespace Binaryk\LaravelRestify\Tests\Fields;

use ArrayObject;
use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Fields\Base64File;
use Binaryk\LaravelRestify\Fields\File;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Storable;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use RuntimeException;

/**
 * A file field handles each row of a bulk store or a bulk update with that row's own upload, URL or base64
 * content, the same way it handles a single store or update. No row's file or value reaches another row.
 */
class FileBulkRequestTest extends IntegrationTestCase
{
    private const DISK = 'customDisk';

    /**
     * A file on the same disk that belongs to a record the request does not name.
     */
    private const OTHER_FILE = 'other.jpg';

    private const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private const JPEG = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AVN//2Q==';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(self::DISK);
        Storage::disk(self::DISK)->put(self::OTHER_FILE, 'other');
    }

    /**
     * @return array<string, array{class-string<File>}>
     */
    public static function fieldClasses(): array
    {
        return FileUrlInputTest::fieldClasses();
    }

    #[DataProvider('fieldClasses')]
    public function test_bulk_store_stores_the_upload_of_each_row_on_that_row(string $fieldClass): void
    {
        $this->fields(fn (): File => $fieldClass::make('avatar')
            ->disk(self::DISK)
            ->storeOriginalName('avatar_original')
            ->storeSize('avatar_size'));

        $this->postForm(UserRepository::route('bulk'), [
            $this->row('first', ['avatar' => UploadedFile::fake()->createWithContent('first.txt', 'first row')]),
            $this->row('second', ['avatar' => UploadedFile::fake()->createWithContent('second.txt', 'the second row')]),
        ])->assertOk();

        $first = $this->user('first');
        $second = $this->user('second');

        $this->assertNotSame($first->avatar, $second->avatar);
        $this->assertSame('first row', Storage::disk(self::DISK)->get($first->avatar));
        $this->assertSame('the second row', Storage::disk(self::DISK)->get($second->avatar));
        $this->assertSame(['first.txt', 'second.txt'], [$first->avatar_original, $second->avatar_original]);
        $this->assertEquals([9, 14], [$first->avatar_size, $second->avatar_size]);
        $this->assertStoredFiles(2);
    }

    #[DataProvider('fieldClasses')]
    public function test_bulk_store_leaves_the_column_of_a_row_without_a_file_empty(string $fieldClass): void
    {
        $this->fields(fn (): File => $fieldClass::make('avatar')
            ->disk(self::DISK)
            ->storeOriginalName('avatar_original')
            ->storeSize('avatar_size'));

        $this->postForm(UserRepository::route('bulk'), [
            $this->row('first'),
            $this->row('second', ['avatar' => UploadedFile::fake()->createWithContent('second.txt', 'second row')]),
            $this->row('third'),
        ])->assertOk();

        foreach (['first', 'third'] as $name) {
            $this->assertSame([null, null, null], $this->avatarColumns($this->user($name)));
        }

        $this->assertSame('second row', Storage::disk(self::DISK)->get($this->user('second')->avatar));
        $this->assertStoredFiles(1);
    }

    #[DataProvider('fieldClasses')]
    public function test_bulk_store_stores_the_remote_url_of_each_row(string $fieldClass): void
    {
        $this->fields(fn (): File => $fieldClass::make('avatar')->disk(self::DISK)->storeOriginalName('avatar_original'));

        $this->postJson(UserRepository::route('bulk'), [
            $this->row('first', ['avatar' => 'https://example.com/images/first.jpg']),
            $this->row('second'),
            $this->row('third', ['avatar' => 'http://example.com/images/third.png']),
        ])->assertOk();

        $this->assertSame(['https://example.com/images/first.jpg', 'first.jpg', null], $this->avatarColumns($this->user('first')));
        $this->assertSame([null, null, null], $this->avatarColumns($this->user('second')));
        $this->assertSame(['http://example.com/images/third.png', 'third.png', null], $this->avatarColumns($this->user('third')));
        $this->assertStoredFiles(0);
    }

    #[DataProviderExternal(FileUrlInputTest::class, 'fieldClassesWithStringsThatAreNotStored')]
    public function test_bulk_store_ignores_a_string_that_is_not_a_remote_url(string $fieldClass, string $input): void
    {
        $this->fields(fn (): File => $fieldClass::make('avatar')->disk(self::DISK)->storeOriginalName('avatar_original'));

        $this->postJson(UserRepository::route('bulk'), [
            $this->row('first', ['avatar' => $input]),
            $this->row('second', ['avatar' => 'https://example.com/images/second.jpg']),
        ])->assertOk();

        $this->assertSame([null, null, null], $this->avatarColumns($this->user('first')));
        $this->assertSame('https://example.com/images/second.jpg', $this->user('second')->avatar);
        $this->assertStoredFiles(0);
    }

    public function test_bulk_store_stores_the_base64_content_of_each_row(): void
    {
        $this->fields(fn (): File => Base64File::make('avatar')
            ->disk(self::DISK)
            ->storeOriginalName('avatar_original')
            ->storeSize('avatar_size'));

        $this->postJson(UserRepository::route('bulk'), [
            $this->row('first', ['avatar' => self::PNG]),
            $this->row('second'),
            $this->row('third', ['avatar' => self::JPEG]),
        ])->assertOk();

        $first = $this->user('first');
        $third = $this->user('third');

        $this->assertStringEndsWith('.png', $first->avatar);
        $this->assertStringEndsWith('.jpg', $third->avatar);
        $this->assertSame($this->decoded(self::PNG), Storage::disk(self::DISK)->get($first->avatar));
        $this->assertSame($this->decoded(self::JPEG), Storage::disk(self::DISK)->get($third->avatar));
        $this->assertSame(['base64-upload.png', 'base64-upload.jpg'], [$first->avatar_original, $third->avatar_original]);
        $this->assertEquals([72, 162], [$first->avatar_size, $third->avatar_size]);
        $this->assertSame([null, null, null], $this->avatarColumns($this->user('second')));
        $this->assertStoredFiles(2);
    }

    #[DataProvider('fieldClasses')]
    public function test_bulk_update_replaces_and_prunes_the_files_of_the_rows_it_names_only(string $fieldClass): void
    {
        $this->fields(fn (): File => $fieldClass::make('avatar')
            ->disk(self::DISK)
            ->prunable()
            ->storeOriginalName('avatar_original')
            ->storeSize('avatar_size'));

        [$first, $second, $third] = $this->usersHolding('old-first.txt', 'old-second.txt', 'old-third.txt');

        // The rows come in another order than the models were created in, and the second user gets no file.
        $this->postForm(UserRepository::route('bulk/update'), [
            ['id' => $third->getKey(), 'avatar' => UploadedFile::fake()->createWithContent('third.txt', 'new third')],
            ['id' => $second->getKey(), 'name' => 'Renamed'],
            ['id' => $first->getKey(), 'avatar' => UploadedFile::fake()->createWithContent('first.txt', 'new first')],
        ])->assertOk();

        $first->refresh();
        $second->refresh();
        $third->refresh();

        $this->assertSame('new first', Storage::disk(self::DISK)->get($first->avatar));
        $this->assertSame('new third', Storage::disk(self::DISK)->get($third->avatar));
        $this->assertSame(['first.txt', 'third.txt'], [$first->avatar_original, $third->avatar_original]);
        $this->assertEquals([9, 9], [$first->avatar_size, $third->avatar_size]);

        $this->assertSame('Renamed', $second->name);
        $this->assertSame(['old-second.txt', 'original.txt', 1024.0], $this->avatarColumns($second));

        Storage::disk(self::DISK)->assertMissing(['old-first.txt', 'old-third.txt']);
        Storage::disk(self::DISK)->assertExists('old-second.txt');
        $this->assertStoredFiles(3);
    }

    public function test_bulk_update_keeps_the_replaced_file_of_a_field_that_is_not_prunable(): void
    {
        $this->fields(fn (): File => File::make('avatar')->disk(self::DISK));

        [$first, $second] = $this->usersHolding('old-first.txt', 'old-second.txt');

        $this->postForm(UserRepository::route('bulk/update'), [
            ['id' => $first->getKey(), 'avatar' => UploadedFile::fake()->createWithContent('first.txt', 'new first')],
            ['id' => $second->getKey(), 'avatar' => UploadedFile::fake()->createWithContent('second.txt', 'new second')],
        ])->assertOk();

        $this->assertSame('new first', Storage::disk(self::DISK)->get($first->fresh()->avatar));
        $this->assertSame('new second', Storage::disk(self::DISK)->get($second->fresh()->avatar));
        Storage::disk(self::DISK)->assertExists(['old-first.txt', 'old-second.txt']);
        $this->assertStoredFiles(4);
    }

    #[DataProvider('fieldClasses')]
    public function test_bulk_update_stores_the_remote_url_of_each_row_and_prunes_that_rows_file(string $fieldClass): void
    {
        $this->fields(fn (): File => $fieldClass::make('avatar')->disk(self::DISK)->prunable()->storeOriginalName('avatar_original'));

        [$first, $second] = $this->usersHolding('old-first.txt', 'old-second.txt');

        $this->postJson(UserRepository::route('bulk/update'), [
            ['id' => $first->getKey(), 'avatar' => 'https://example.com/images/first.jpg'],
            ['id' => $second->getKey(), 'name' => 'Renamed'],
        ])->assertOk();

        $this->assertSame(['https://example.com/images/first.jpg', 'first.jpg', 1024.0], $this->avatarColumns($first->fresh()));
        $this->assertSame(['old-second.txt', 'original.txt', 1024.0], $this->avatarColumns($second->fresh()));
        Storage::disk(self::DISK)->assertMissing('old-first.txt');
        Storage::disk(self::DISK)->assertExists('old-second.txt');
        $this->assertStoredFiles(1);
    }

    public function test_bulk_update_stores_the_base64_content_of_each_row_and_prunes_that_rows_file(): void
    {
        $this->fields(fn (): File => Base64File::make('avatar')->disk(self::DISK)->prunable());

        [$first, $second, $third] = $this->usersHolding('old-first.txt', 'old-second.txt', 'old-third.txt');

        $this->postJson(UserRepository::route('bulk/update'), [
            ['id' => $first->getKey(), 'avatar' => self::PNG],
            ['id' => $third->getKey(), 'avatar' => self::JPEG],
        ])->assertOk();

        $this->assertSame($this->decoded(self::PNG), Storage::disk(self::DISK)->get($first->fresh()->avatar));
        $this->assertSame($this->decoded(self::JPEG), Storage::disk(self::DISK)->get($third->fresh()->avatar));
        $this->assertSame('old-second.txt', $second->fresh()->avatar);
        Storage::disk(self::DISK)->assertMissing(['old-first.txt', 'old-third.txt']);
        Storage::disk(self::DISK)->assertExists('old-second.txt');
        $this->assertStoredFiles(3);
    }

    /**
     * One row carries a value that is not an upload, a remote URL or base64 content, such as a path to
     * another record's file. That row keeps its file, and the row next to it is still written.
     */
    #[DataProviderExternal(FileUrlInputTest::class, 'fieldClassesWithStringsThatAreNotStored')]
    public function test_bulk_update_ignores_a_string_that_is_not_a_remote_url_and_deletes_nothing_for_it(string $fieldClass, string $input): void
    {
        $this->fields(fn (): File => $fieldClass::make('avatar')->disk(self::DISK)->prunable()->storeOriginalName('avatar_original'));

        [$first, $second] = $this->usersHolding('old-first.txt', 'old-second.txt');

        $this->postForm(UserRepository::route('bulk/update'), [
            ['id' => $first->getKey(), 'avatar' => $input],
            ['id' => $second->getKey(), 'avatar' => UploadedFile::fake()->createWithContent('second.txt', 'new second')],
        ])->assertOk();

        $this->assertSame(['old-first.txt', 'original.txt', 1024.0], $this->avatarColumns($first->fresh()));
        Storage::disk(self::DISK)->assertExists(['old-first.txt', self::OTHER_FILE]);

        $this->assertSame('new second', Storage::disk(self::DISK)->get($second->fresh()->avatar));
        Storage::disk(self::DISK)->assertMissing('old-second.txt');
        $this->assertStoredFiles(2);
    }

    /**
     * The column of one row holds a URL or a value with a ".." segment, which the disk would resolve to
     * another record's file. Replacing it deletes no file.
     */
    #[DataProviderExternal(FileUrlInputTest::class, 'fieldClassesWithStoredValuesThatAreNotPaths')]
    public function test_bulk_update_replaces_a_stored_value_that_is_not_a_path_without_deleting_a_file(string $fieldClass, string $stored): void
    {
        $this->fields(fn (): File => $fieldClass::make('avatar')->disk(self::DISK)->prunable());

        [$first, $second] = $this->usersHolding($stored, 'old-second.txt');

        $this->postForm(UserRepository::route('bulk/update'), [
            ['id' => $first->getKey(), 'avatar' => UploadedFile::fake()->createWithContent('first.txt', 'new first')],
            ['id' => $second->getKey(), 'avatar' => UploadedFile::fake()->createWithContent('second.txt', 'new second')],
        ])->assertOk();

        $this->assertSame('new first', Storage::disk(self::DISK)->get($first->fresh()->avatar));
        $this->assertSame('new second', Storage::disk(self::DISK)->get($second->fresh()->avatar));
        Storage::disk(self::DISK)->assertExists(self::OTHER_FILE);
        Storage::disk(self::DISK)->assertMissing('old-second.txt');
        $this->assertStoredFiles(2);
    }

    #[DataProvider('fieldClasses')]
    public function test_bulk_store_skips_a_file_field_the_user_cannot_store(string $fieldClass): void
    {
        $this->fields(fn (): File => $fieldClass::make('avatar')->disk(self::DISK)->canStore(fn (): bool => false));

        $this->postForm(UserRepository::route('bulk'), [
            $this->row('first', ['avatar' => UploadedFile::fake()->createWithContent('first.txt', 'first row')]),
            $this->row('second', ['avatar' => 'https://example.com/images/second.jpg']),
            $this->row('third', ['avatar' => self::PNG]),
        ])->assertOk();

        foreach (['first', 'second', 'third'] as $name) {
            $this->assertNull($this->user($name)->avatar);
        }

        $this->assertStoredFiles(0);
    }

    public function test_bulk_store_asks_can_store_for_each_row(): void
    {
        $this->fields(fn (): File => File::make('avatar')->disk(self::DISK)->canStore(
            fn (Request $request): bool => $request->input(array_key_first($request->all()).'.name') !== 'second'
        ));

        $this->postForm(UserRepository::route('bulk'), [
            $this->row('first', ['avatar' => UploadedFile::fake()->createWithContent('first.txt', 'first row')]),
            $this->row('second', ['avatar' => UploadedFile::fake()->createWithContent('second.txt', 'second row')]),
            $this->row('third', ['avatar' => UploadedFile::fake()->createWithContent('third.txt', 'third row')]),
        ])->assertOk();

        $this->assertSame('first row', Storage::disk(self::DISK)->get($this->user('first')->avatar));
        $this->assertNull($this->user('second')->avatar);
        $this->assertSame('third row', Storage::disk(self::DISK)->get($this->user('third')->avatar));
        $this->assertStoredFiles(2);
    }

    /**
     * @return array<string, array{Closure(File): File}>
     */
    public static function fieldsBulkRequestsSkip(): array
    {
        return [
            'canUpdateBulk denies' => [fn (File $field): File => $field->canUpdateBulk(fn (): bool => false)],
            'readonly' => [fn (File $field): File => $field->readonly()],
        ];
    }

    #[DataProvider('fieldsBulkRequestsSkip')]
    public function test_bulk_update_skips_a_file_field_the_user_cannot_update(Closure $deny): void
    {
        $this->fields(fn (): File => $deny(File::make('avatar')->disk(self::DISK)->prunable()));

        [$first, $second] = $this->usersHolding('old-first.txt', 'old-second.txt');

        $this->postForm(UserRepository::route('bulk/update'), [
            ['id' => $first->getKey(), 'name' => 'Renamed', 'avatar' => UploadedFile::fake()->createWithContent('first.txt', 'new first')],
            ['id' => $second->getKey(), 'avatar' => 'https://example.com/images/second.jpg'],
        ])->assertOk();

        $this->assertSame('Renamed', $first->fresh()->name);
        $this->assertSame(['old-first.txt', 'original.txt', 1024.0], $this->avatarColumns($first->fresh()));
        $this->assertSame(['old-second.txt', 'original.txt', 1024.0], $this->avatarColumns($second->fresh()));
        Storage::disk(self::DISK)->assertExists(['old-first.txt', 'old-second.txt']);
        $this->assertStoredFiles(2);
    }

    #[DataProvider('fieldsBulkRequestsSkip')]
    public function test_bulk_store_skips_a_file_field_bulk_requests_may_not_write(Closure $deny): void
    {
        $this->fields(fn (): File => $deny(File::make('avatar')->disk(self::DISK)));

        $this->postForm(UserRepository::route('bulk'), [
            $this->row('first', ['avatar' => UploadedFile::fake()->createWithContent('first.txt', 'first row')]),
        ])->assertOk();

        $this->assertNull($this->user('first')->avatar);
        $this->assertStoredFiles(0);
    }

    /**
     * Rules, a file that passes them, and what a row sends to break them (null for no file at all).
     *
     * @return array<string, array{list<string>, Closure(): UploadedFile, Closure(): (UploadedFile|string|null)}>
     */
    public static function fileRules(): array
    {
        $pdf = fn (): UploadedFile => UploadedFile::fake()->create('document.pdf', 1, 'application/pdf');

        return [
            'file' => [['file'], $pdf, fn (): string => 'not-a-file'],
            'image' => [['image'], fn (): UploadedFile => UploadedFile::fake()->image('photo.jpg'), $pdf],
            'mimes' => [['mimes:pdf'], $pdf, fn (): UploadedFile => UploadedFile::fake()->image('photo.jpg')],
            'max' => [['file', 'max:1'], $pdf, fn (): UploadedFile => UploadedFile::fake()->create('document.pdf', 2, 'application/pdf')],
            'required' => [['required', 'file'], $pdf, fn (): null => null],
        ];
    }

    /**
     * @param  list<string>  $rules
     * @param  Closure(): UploadedFile  $valid
     * @param  Closure(): (UploadedFile|string|null)  $invalid
     */
    #[DataProvider('fileRules')]
    public function test_bulk_store_reports_a_file_rule_under_the_key_of_the_row_that_breaks_it(array $rules, Closure $valid, Closure $invalid): void
    {
        $this->fields(fn (): File => File::make('avatar')->disk(self::DISK)->storeBulkRules(...$rules));

        $this->postForm(UserRepository::route('bulk'), [
            $this->row('first', ['avatar' => $valid()]),
            $this->row('second', array_filter(['avatar' => $invalid()])),
            $this->row('third', ['avatar' => $valid()]),
        ])->assertUnprocessable()->assertJsonValidationErrors(['1.avatar'])->assertJsonMissingValidationErrors(['0.avatar', '2.avatar']);

        $this->assertDatabaseMissing(User::class, ['email' => 'first@example.com']);
        $this->assertStoredFiles(0);
    }

    /**
     * @param  list<string>  $rules
     * @param  Closure(): UploadedFile  $valid
     * @param  Closure(): (UploadedFile|string|null)  $invalid
     */
    #[DataProvider('fileRules')]
    public function test_bulk_update_reports_a_file_rule_under_the_key_of_the_row_that_breaks_it(array $rules, Closure $valid, Closure $invalid): void
    {
        $this->fields(fn (): File => File::make('avatar')->disk(self::DISK)->prunable()->updateBulkRules(...$rules));

        [$first, $second] = $this->usersHolding('old-first.txt', 'old-second.txt');

        $this->postForm(UserRepository::route('bulk/update'), [
            ['id' => $first->getKey(), 'avatar' => $valid()],
            array_filter(['id' => $second->getKey(), 'avatar' => $invalid()]),
        ])->assertUnprocessable()->assertJsonValidationErrors(['1.avatar'])->assertJsonMissingValidationErrors(['0.avatar']);

        $this->assertSame('old-first.txt', $first->fresh()->avatar);
        $this->assertSame('old-second.txt', $second->fresh()->avatar);
        Storage::disk(self::DISK)->assertExists(['old-first.txt', 'old-second.txt']);
        $this->assertStoredFiles(2);
    }

    /**
     * @return array<string, array{list<string>, Closure(): UploadedFile}>
     */
    public static function fileRulesWithAFileThatPasses(): array
    {
        return array_map(static fn (array $case): array => [$case[0], $case[1]], self::fileRules());
    }

    /**
     * @param  list<string>  $rules
     * @param  Closure(): UploadedFile  $valid
     */
    #[DataProvider('fileRulesWithAFileThatPasses')]
    public function test_bulk_store_stores_the_files_that_pass_the_rules(array $rules, Closure $valid): void
    {
        $this->fields(fn (): File => File::make('avatar')->disk(self::DISK)->rules(...$rules));

        $this->postForm(UserRepository::route('bulk'), [
            $this->row('first', ['avatar' => $valid()]),
            $this->row('second', ['avatar' => $valid()]),
        ])->assertOk();

        Storage::disk(self::DISK)->assertExists([$this->user('first')->avatar, $this->user('second')->avatar]);
        $this->assertStoredFiles(2);
    }

    public function test_bulk_store_names_each_rows_file_with_the_store_as_callback(): void
    {
        $arguments = new ArrayObject;

        $this->fields(fn (): File => File::make('avatar')
            ->disk(self::DISK)
            ->path('avatars')
            ->storeOriginalName('avatar_original')
            ->storeAs(function (Request $request, int $row) use ($arguments): string {
                $arguments[] = func_num_args();

                return "{$row}-".$request->input("{$row}.name");
            }));

        $this->postForm(UserRepository::route('bulk'), [
            $this->row('first', ['avatar' => UploadedFile::fake()->createWithContent('a.txt', 'first row')]),
            $this->row('second', ['avatar' => UploadedFile::fake()->createWithContent('b.txt', 'second row')]),
        ])->assertOk();

        $this->assertSame([2, 2], $arguments->getArrayCopy());
        $this->assertSame(['avatars/0-first.txt', '0-first.txt', null], $this->avatarColumns($this->user('first')));
        $this->assertSame(['avatars/1-second.txt', '1-second.txt', null], $this->avatarColumns($this->user('second')));
        $this->assertSame('first row', Storage::disk(self::DISK)->get('avatars/0-first.txt'));
        $this->assertSame('second row', Storage::disk(self::DISK)->get('avatars/1-second.txt'));
    }

    public function test_bulk_store_names_each_rows_base64_file_with_the_store_as_callback(): void
    {
        $this->fields(fn (): File => Base64File::make('avatar')
            ->disk(self::DISK)
            ->storeAs(fn (Request $request, int $row): string => "{$row}-".$request->input("{$row}.name")));

        $this->postJson(UserRepository::route('bulk'), [
            $this->row('first', ['avatar' => self::PNG]),
            $this->row('second', ['avatar' => self::JPEG]),
        ])->assertOk();

        $this->assertSame('0-first.png', $this->user('first')->avatar);
        $this->assertSame('1-second.jpg', $this->user('second')->avatar);
        $this->assertSame($this->decoded(self::PNG), Storage::disk(self::DISK)->get('0-first.png'));
        $this->assertSame($this->decoded(self::JPEG), Storage::disk(self::DISK)->get('1-second.jpg'));
    }

    public function test_bulk_store_hands_the_row_to_a_custom_store_callback(): void
    {
        $arguments = new ArrayObject;

        $this->fields(fn (): File => File::make('avatar')->disk(self::DISK)->path('avatars')->store(
            function (Request $request, User $model, string $attribute, string $disk, string $path, int $row) use ($arguments): array {
                $arguments[] = [func_num_args(), $attribute, $disk, $path, $row, array_keys($request->allFiles())];

                return [
                    $attribute => $request->file("{$row}.{$attribute}")->storeAs($path, "custom-{$row}.txt", $disk),
                    'avatar_original' => "row {$row}",
                ];
            }
        ));

        $this->postForm(UserRepository::route('bulk'), [
            $this->row('first', ['avatar' => UploadedFile::fake()->createWithContent('a.txt', 'first row')]),
            $this->row('second'),
            $this->row('third', ['avatar' => UploadedFile::fake()->createWithContent('c.txt', 'third row')]),
        ])->assertOk();

        $this->assertSame([
            [6, 'avatar', self::DISK, 'avatars', 0, [0]],
            [6, 'avatar', self::DISK, 'avatars', 2, [2]],
        ], $arguments->getArrayCopy());
        $this->assertSame(['avatars/custom-0.txt', 'row 0', null], $this->avatarColumns($this->user('first')));
        $this->assertSame([null, null, null], $this->avatarColumns($this->user('second')));
        $this->assertSame(['avatars/custom-2.txt', 'row 2', null], $this->avatarColumns($this->user('third')));
        $this->assertSame('third row', Storage::disk(self::DISK)->get('avatars/custom-2.txt'));
    }

    public function test_bulk_update_hands_the_row_to_a_storable(): void
    {
        $storable = new class implements Storable
        {
            public function handle(Request $request, Model $model, $attribute, ?string $disk = null, ?string $path = null, ?int $row = null): array
            {
                return [
                    $attribute => $request->file("{$row}.{$attribute}")->storeAs('/', "storable-{$model->getKey()}.txt", $disk),
                ];
            }
        };

        $this->fields(fn (): File => File::make('avatar')->disk(self::DISK)->prunable()->store($storable::class));

        [$first, $second] = $this->usersHolding('old-first.txt', 'old-second.txt');

        $this->postForm(UserRepository::route('bulk/update'), [
            ['id' => $second->getKey(), 'avatar' => UploadedFile::fake()->createWithContent('b.txt', 'new second')],
            ['id' => $first->getKey(), 'avatar' => UploadedFile::fake()->createWithContent('a.txt', 'new first')],
        ])->assertOk();

        $this->assertSame("storable-{$first->getKey()}.txt", $first->fresh()->avatar);
        $this->assertSame("storable-{$second->getKey()}.txt", $second->fresh()->avatar);
        $this->assertSame('new first', Storage::disk(self::DISK)->get($first->fresh()->avatar));
        $this->assertSame('new second', Storage::disk(self::DISK)->get($second->fresh()->avatar));
        Storage::disk(self::DISK)->assertMissing(['old-first.txt', 'old-second.txt']);
    }

    public function test_bulk_store_hands_the_row_to_the_store_callback_that_fills_the_field(): void
    {
        $arguments = new ArrayObject;

        $this->fields(fn (): File => File::make('avatar')->disk(self::DISK)->storeCallback(
            function (RestifyRequest $request, User $model, string $attribute, int $row) use ($arguments): void {
                $arguments[] = [func_num_args(), $attribute, $row];

                $model->avatar = 'filled for '.$request->input("{$row}.name");
            }
        ));

        $this->postForm(UserRepository::route('bulk'), [
            $this->row('first', ['avatar' => UploadedFile::fake()->createWithContent('a.txt', 'first row')]),
            $this->row('second'),
        ])->assertOk();

        $this->assertSame([[4, 'avatar', 0], [4, 'avatar', 1]], $arguments->getArrayCopy());
        $this->assertSame('filled for first', $this->user('first')->avatar);
        $this->assertSame('filled for second', $this->user('second')->avatar);
        $this->assertStoredFiles(0);
    }

    public function test_a_single_store_passes_its_callbacks_no_row(): void
    {
        $arguments = new ArrayObject;

        $fields = [
            'first' => fn (): File => File::make('avatar')
                ->disk(self::DISK)
                ->storeAs(function (Request $request) use ($arguments): string {
                    $arguments['storeAs'] = func_num_args();

                    return 'single';
                }),
            'second' => fn (): File => File::make('avatar')->disk(self::DISK)->store(
                function (Request $request, User $model, string $attribute, string $disk) use ($arguments): array {
                    $arguments['store'] = func_num_args();

                    return [$attribute => $request->file($attribute)->storeAs('/', 'custom.txt', $disk)];
                }
            ),
            'third' => fn (): File => File::make('avatar')->disk(self::DISK)->storeCallback(
                function (RestifyRequest $request, User $model, string $attribute) use ($arguments): void {
                    $arguments['storeCallback'] = func_num_args();

                    $model->avatar = 'filled';
                }
            ),
        ];

        $name = 'first';

        $this->fields(function () use ($fields, &$name): File {
            return $fields[$name]();
        });

        foreach (array_keys($fields) as $name) {
            $this->postForm(UserRepository::route(), $this->row($name, [
                'avatar' => UploadedFile::fake()->createWithContent('a.txt', $name),
            ]))->assertCreated();
        }

        $this->assertSame(['storeAs' => 1, 'store' => 5, 'storeCallback' => 3], $arguments->getArrayCopy());
        $this->assertSame(['single.txt', 'custom.txt', 'filled'], [
            $this->user('first')->avatar,
            $this->user('second')->avatar,
            $this->user('third')->avatar,
        ]);
    }

    /**
     * A store callback may return the path alone instead of an array of columns.
     */
    public function test_a_store_callback_may_return_the_path_alone(): void
    {
        $this->fields(fn (): File => File::make('avatar')->disk(self::DISK)->store(
            fn (Request $request, User $model, string $attribute, string $disk, string $path, ?int $row = null): string => $request
                ->file($row === null ? $attribute : "{$row}.{$attribute}")
                ->storeAs('/', $request->input($row === null ? 'name' : "{$row}.name").'.txt', $disk)
        ));

        $this->postForm(UserRepository::route(), $this->row('single', [
            'avatar' => UploadedFile::fake()->createWithContent('a.txt', 'single'),
        ]))->assertCreated();

        $this->postForm(UserRepository::route('bulk'), [
            $this->row('first', ['avatar' => UploadedFile::fake()->createWithContent('a.txt', 'first row')]),
            $this->row('second', ['avatar' => UploadedFile::fake()->createWithContent('b.txt', 'second row')]),
        ])->assertOk();

        $this->assertSame(['single.txt', 'first.txt', 'second.txt'], [
            $this->user('single')->avatar,
            $this->user('first')->avatar,
            $this->user('second')->avatar,
        ]);
        $this->assertSame('second row', Storage::disk(self::DISK)->get('second.txt'));
    }

    public function test_a_field_action_on_a_file_field_runs_for_each_row_after_its_file_is_stored(): void
    {
        $action = new class extends Action
        {
            public bool $skipFieldFill = false;

            /**
             * @var list<array{int, string, bool}>
             */
            public static array $handled = [];

            public function handle(RestifyRequest $request, User $user, int $row): void
            {
                self::$handled[] = [$row, $user->name, Storage::disk('customDisk')->exists($user->avatar)];
            }
        };

        $this->fields(fn (): File => File::make('avatar')->disk(self::DISK)->action($action));

        $this->postForm(UserRepository::route('bulk'), [
            $this->row('first', ['avatar' => UploadedFile::fake()->createWithContent('a.txt', 'first row')]),
            $this->row('second'),
            $this->row('third', ['avatar' => UploadedFile::fake()->createWithContent('c.txt', 'third row')]),
        ])->assertOk();

        $this->assertSame([[0, 'first', true], [2, 'third', true]], $action::$handled);
        $this->assertSame('third row', Storage::disk(self::DISK)->get($this->user('third')->avatar));
        $this->assertNull($this->user('second')->avatar);
    }

    /**
     * Storing a file is not part of the transaction: a row that fails rolls back the rows, not the disk. A
     * single store whose save fails leaves its file the same way.
     */
    public function test_bulk_store_rolls_back_the_rows_but_keeps_the_files_stored_before_a_row_failed(): void
    {
        $this->fields(fn (): File => File::make('avatar')->disk(self::DISK));

        $this->failWhenSaving('second');

        $this->postForm(UserRepository::route('bulk'), [
            $this->row('first', ['avatar' => UploadedFile::fake()->createWithContent('a.txt', 'first row')]),
            $this->row('second', ['avatar' => UploadedFile::fake()->createWithContent('b.txt', 'second row')]),
            $this->row('third', ['avatar' => UploadedFile::fake()->createWithContent('c.txt', 'third row')]),
        ])->assertServerError();

        $this->assertDatabaseCount(User::class, 0);
        $this->assertStoredFiles(2);
    }

    /**
     * A prunable field deletes the file it replaces while its row is filled, so that file is gone when a
     * later row fails, although the column is rolled back to it.
     */
    public function test_bulk_update_rolls_back_the_rows_but_not_the_files_replaced_before_a_row_failed(): void
    {
        $this->fields(fn (): File => File::make('avatar')->disk(self::DISK)->prunable());

        [$first, $second] = $this->usersHolding('old-first.txt', 'old-second.txt');

        $this->failWhenSaving('second');

        $this->postForm(UserRepository::route('bulk/update'), [
            ['id' => $first->getKey(), 'avatar' => UploadedFile::fake()->createWithContent('a.txt', 'new first')],
            ['id' => $second->getKey(), 'name' => 'second', 'avatar' => UploadedFile::fake()->createWithContent('b.txt', 'new second')],
        ])->assertServerError();

        $this->assertSame('old-first.txt', $first->fresh()->avatar);
        $this->assertSame('old-second.txt', $second->fresh()->avatar);
        Storage::disk(self::DISK)->assertMissing(['old-first.txt', 'old-second.txt']);
        $this->assertStoredFiles(2);
    }

    public function test_the_field_reads_the_row_it_is_given_and_the_top_level_without_one(): void
    {
        $field = File::make('avatar')->disk(self::DISK);

        $request = RestifyRequest::create('/', 'POST', [
            'avatar' => 'https://example.com/images/single.jpg',
            0 => ['avatar' => 'https://example.com/images/first.jpg'],
            1 => ['avatar' => 'https://example.com/images/second.jpg'],
            2 => ['name' => 'no avatar'],
        ]);

        $avatars = [];

        foreach ([1, null, 0, 2, null] as $row) {
            $field->fillAttribute($request, $model = new User, $row);

            $avatars[] = $model->avatar;
        }

        $this->assertSame([
            'https://example.com/images/second.jpg',
            'https://example.com/images/single.jpg',
            'https://example.com/images/first.jpg',
            null,
            'https://example.com/images/single.jpg',
        ], $avatars);
    }

    /**
     * The repository fields: name, email and password, next to a file field built for every row, as a
     * repository builds its fields.
     *
     * @param  Closure(): File  $file
     */
    private function fields(Closure $file): void
    {
        UserRepository::partialMock()->shouldReceive('fields')->andReturnUsing(fn (): array => [
            field('name'),
            field('email'),
            field('password'),
            $file(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function row(string $name, array $input = []): array
    {
        return ['name' => $name, 'email' => "{$name}@example.com", 'password' => 'secret', ...$input];
    }

    private function user(string $name): User
    {
        return User::query()->where('email', "{$name}@example.com")->sole();
    }

    /**
     * Users whose avatar column holds the given values, written straight to the database. A value that is a
     * path on the disk also gets a file there.
     *
     * @return list<User>
     */
    private function usersHolding(string ...$stored): array
    {
        return array_map(function (string $stored): User {
            if (File::isStoragePath($stored)) {
                Storage::disk(self::DISK)->put($stored, 'old');
            }

            $user = User::factory()->create();

            $user->forceFill([
                'avatar' => $stored,
                'avatar_original' => 'original.txt',
                'avatar_size' => 1024,
            ])->save();

            return $user;
        }, array_values($stored));
    }

    /**
     * @return array{string|null, string|null, float|null}
     */
    private function avatarColumns(User $user): array
    {
        return [$user->avatar, $user->avatar_original, $user->avatar_size === null ? null : (float) $user->avatar_size];
    }

    /**
     * A multipart request, as a client sends its uploads.
     *
     * @param  array<array-key, mixed>  $data
     */
    private function postForm(string $uri, array $data): TestResponse
    {
        return $this->post($uri, $data, ['Accept' => 'application/json']);
    }

    private function decoded(string $dataUri): string
    {
        return base64_decode(explode(',', $dataUri)[1]);
    }

    /**
     * Assert how many files the disk holds, next to the one that belongs to another record.
     */
    private function assertStoredFiles(int $count): void
    {
        $this->assertCount($count, array_diff(Storage::disk(self::DISK)->allFiles(), [self::OTHER_FILE]));
        Storage::disk(self::DISK)->assertExists(self::OTHER_FILE);
    }

    private function failWhenSaving(string $name): void
    {
        Event::listen('eloquent.saving: '.User::class, static function (User $user) use ($name): void {
            if ($user->name === $name) {
                throw new RuntimeException('Row failed');
            }
        });
    }
}
