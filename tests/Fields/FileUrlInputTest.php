<?php

namespace Binaryk\LaravelRestify\Tests\Fields;

use Binaryk\LaravelRestify\Fields\Base64File;
use Binaryk\LaravelRestify\Fields\File;
use Binaryk\LaravelRestify\Fields\Image;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * A file column holds either a path the field stored on its disk or, through URL input, an external URL.
 * These tests pin both halves of that: which strings URL input stores, and that nothing but a path on the
 * disk is ever handed to the disk.
 */
class FileUrlInputTest extends IntegrationTestCase
{
    private const DISK = 'customDisk';

    private const OWN_FILE = 'avatar.jpg';

    /**
     * A file on the same disk that belongs to another record.
     */
    private const OTHER_FILE = 'other.jpg';

    private const REMOTE_URL = 'https://example.com/images/avatar.jpg';

    private const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(self::DISK);
        Storage::disk(self::DISK)->put(self::OWN_FILE, 'own');
        Storage::disk(self::DISK)->put(self::OTHER_FILE, 'other');
    }

    /**
     * @return array<string, array{class-string<File>}>
     */
    public static function fieldClasses(): array
    {
        return [
            'file' => [File::class],
            'image' => [Image::class],
            'base64 file' => [Base64File::class],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function remoteUrls(): array
    {
        return [
            'https' => [self::REMOTE_URL],
            'http' => ['http://example.com/images/avatar.jpg'],
            'uppercase scheme' => ['HTTPS://example.com/images/avatar.jpg'],
            'port' => ['https://example.com:8443/images/avatar.jpg'],
            'ip host' => ['https://203.0.113.10/images/avatar.jpg'],
            'dots inside a segment' => ['https://example.com/images/v1..2/avatar.jpg'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function stringsThatAreNotStored(): array
    {
        return [
            'ftp url' => ['ftp://example.com/avatar.jpg'],
            'file url' => ['file:///avatar.jpg'],
            'mailto url' => ['mailto:someone@example.com'],
            'script url' => ['javascript://example.com/avatar.jpg'],
            'stream wrapper' => ['php://filter/resource=avatar.jpg'],
            'http url with parent segments' => ['http://example.com/../../other.jpg'],
            'https url with parent segments in the query' => ['https://example.com/a?b=/../../../other.jpg'],
            'https url with backslash parent segments' => ['https://example.com/..\\..\\other.jpg'],
            'https url without a host' => ['https:///other.jpg'],
            'protocol relative url' => ['//example.com/other.jpg'],
            'path on the disk' => ['other.jpg'],
            'relative path' => ['../other.jpg'],
        ];
    }

    /**
     * Column values that are not a path the field stored. All but the first are resolved by the disk to
     * the other record's file.
     *
     * @return array<string, array{string}>
     */
    public static function storedValuesThatAreNotPaths(): array
    {
        return [
            'https url' => [self::REMOTE_URL],
            'http url with parent segments' => ['http://example.com/../../other.jpg'],
            'https url with parent segments in the query' => ['https://example.com/a?b=/../../../other.jpg'],
            'https url with backslash parent segments' => ['https://example.com/..\\..\\other.jpg'],
            'scheme without a host' => ['mailto:a/../other.jpg'],
            'path with a parent segment' => ['avatars/../other.jpg'],
            'path with a backslash parent segment' => ['avatars\\..\\other.jpg'],
        ];
    }

    /**
     * @return array<string, array{class-string<File>, string}>
     */
    public static function fieldClassesWithStringsThatAreNotStored(): array
    {
        return self::cross(self::fieldClasses(), self::stringsThatAreNotStored());
    }

    /**
     * @return array<string, array{class-string<File>, string}>
     */
    public static function fieldClassesWithRemoteUrls(): array
    {
        return self::cross(self::fieldClasses(), self::remoteUrls());
    }

    /**
     * @return array<string, array{class-string<File>, string}>
     */
    public static function fieldClassesWithStoredValuesThatAreNotPaths(): array
    {
        return self::cross(self::fieldClasses(), self::storedValuesThatAreNotPaths());
    }

    #[DataProvider('fieldClassesWithStringsThatAreNotStored')]
    public function test_update_ignores_a_string_that_is_not_a_remote_url(string $fieldClass, string $input): void
    {
        $this->fields($fieldClass::make('avatar')->disk(self::DISK)->prunable()->storeOriginalName('avatar_original'));

        $user = $this->userHolding(self::OWN_FILE);

        $this->postJson(UserRepository::route($user), ['avatar' => $input])->assertOk();

        $this->assertSame(self::OWN_FILE, $user->fresh()->avatar);
        $this->assertSame('original.jpg', $user->fresh()->avatar_original);
        $this->assertNoFileWasDeleted();
    }

    #[DataProvider('fieldClassesWithStringsThatAreNotStored')]
    public function test_patch_ignores_a_string_that_is_not_a_remote_url(string $fieldClass, string $input): void
    {
        $this->fields($fieldClass::make('avatar')->disk(self::DISK)->prunable());

        $user = $this->userHolding(self::OWN_FILE);

        $this->patchJson(UserRepository::route($user), ['avatar' => $input])->assertOk();

        $this->assertSame(self::OWN_FILE, $user->fresh()->avatar);
        $this->assertNoFileWasDeleted();
    }

    #[DataProvider('fieldClassesWithStringsThatAreNotStored')]
    public function test_store_ignores_a_string_that_is_not_a_remote_url(string $fieldClass, string $input): void
    {
        $this->fields(
            field('name'),
            field('email'),
            field('password'),
            $fieldClass::make('avatar')->disk(self::DISK)->storeOriginalName('avatar_original'),
        );

        $this->postJson(UserRepository::route(), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret',
            'avatar' => $input,
        ])->assertCreated();

        $user = User::query()->where('email', 'jane@example.com')->sole();

        $this->assertNull($user->avatar);
        $this->assertNull($user->avatar_original);
    }

    #[DataProvider('fieldClassesWithRemoteUrls')]
    public function test_update_stores_a_remote_url(string $fieldClass, string $url): void
    {
        $this->fields($fieldClass::make('avatar')->disk(self::DISK)->storeOriginalName('avatar_original'));

        $user = $this->userHolding(null);

        $response = $this->postJson(UserRepository::route($user), ['avatar' => $url])->assertOk();

        $this->assertSame($url, $user->fresh()->avatar);
        $this->assertSame('avatar.jpg', $user->fresh()->avatar_original);

        if ($fieldClass !== Image::class) {
            $response->assertJsonPath('data.attributes.avatar', $url);
        }
    }

    #[DataProvider('fieldClasses')]
    public function test_patch_stores_a_remote_url(string $fieldClass): void
    {
        $this->fields($fieldClass::make('avatar')->disk(self::DISK));

        $user = $this->userHolding(null);

        $this->patchJson(UserRepository::route($user), ['avatar' => self::REMOTE_URL])->assertOk();

        $this->assertSame(self::REMOTE_URL, $user->fresh()->avatar);
    }

    #[DataProvider('fieldClasses')]
    public function test_store_stores_a_remote_url(string $fieldClass): void
    {
        $this->fields(
            field('name'),
            field('email'),
            field('password'),
            $fieldClass::make('avatar')->disk(self::DISK)->storeOriginalName('avatar_original'),
        );

        $this->postJson(UserRepository::route(), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret',
            'avatar' => self::REMOTE_URL,
        ])->assertCreated();

        $user = User::query()->where('email', 'jane@example.com')->sole();

        $this->assertSame(self::REMOTE_URL, $user->avatar);
        $this->assertSame('avatar.jpg', $user->avatar_original);
    }

    #[DataProvider('fieldClassesWithStoredValuesThatAreNotPaths')]
    public function test_field_delete_clears_the_column_without_deleting_a_file(string $fieldClass, string $stored): void
    {
        $this->fields(
            $fieldClass::make('avatar')->disk(self::DISK)->storeOriginalName('avatar_original')->storeSize('avatar_size')
        );

        $user = $this->userHolding($stored);

        $this->deleteJson(UserRepository::route($user->getKey().'/field/avatar'))->assertNoContent();

        $user = $user->fresh();

        $this->assertNull($user->avatar);
        $this->assertNull($user->avatar_original);
        $this->assertNull($user->avatar_size);
        $this->assertNoFileWasDeleted();
    }

    #[DataProvider('fieldClassesWithStoredValuesThatAreNotPaths')]
    public function test_prunable_upload_replaces_the_value_without_deleting_a_file(string $fieldClass, string $stored): void
    {
        $this->fields($fieldClass::make('avatar')->disk(self::DISK)->prunable()->storeAs('new.jpg'));

        $user = $this->userHolding($stored);

        $this->postJson(UserRepository::route($user), [
            'avatar' => UploadedFile::fake()->image('image.jpg'),
        ])->assertOk();

        $this->assertSame('new.jpg', $user->fresh()->avatar);
        Storage::disk(self::DISK)->assertExists('new.jpg');
        $this->assertNoFileWasDeleted();
    }

    #[DataProvider('fieldClassesWithStoredValuesThatAreNotPaths')]
    public function test_prunable_url_input_replaces_the_value_without_deleting_a_file(string $fieldClass, string $stored): void
    {
        $this->fields($fieldClass::make('avatar')->disk(self::DISK)->prunable());

        $user = $this->userHolding($stored);

        $this->postJson(UserRepository::route($user), [
            'avatar' => 'https://example.com/images/new.jpg',
        ])->assertOk();

        $this->assertSame('https://example.com/images/new.jpg', $user->fresh()->avatar);
        $this->assertNoFileWasDeleted();
    }

    #[DataProvider('storedValuesThatAreNotPaths')]
    public function test_prunable_base64_input_replaces_the_value_without_deleting_a_file(string $stored): void
    {
        $this->fields(Base64File::make('avatar')->disk(self::DISK)->prunable()->storeAs('new'));

        $user = $this->userHolding($stored);

        $this->postJson(UserRepository::route($user), ['avatar' => self::PNG])->assertOk();

        $this->assertSame('new.png', $user->fresh()->avatar);
        Storage::disk(self::DISK)->assertExists('new.png');
        $this->assertNoFileWasDeleted();
    }

    #[DataProvider('fieldClassesWithStoredValuesThatAreNotPaths')]
    public function test_deleting_the_record_prunes_no_file(string $fieldClass, string $stored): void
    {
        $this->fields($fieldClass::make('avatar')->disk(self::DISK)->prunable());

        $user = $this->userHolding($stored);

        $this->deleteJson(UserRepository::route($user))->assertNoContent();

        $this->assertNull($user->fresh());
        $this->assertNoFileWasDeleted();
    }

    #[DataProvider('storedValuesThatAreNotPaths')]
    public function test_image_resolves_to_its_default_without_asking_the_disk(string $stored): void
    {
        $this->fields(Image::make('avatar')->disk(self::DISK)->default('default.png'));

        $user = $this->userHolding($stored);

        $this->getJson(UserRepository::route($user))
            ->assertOk()
            ->assertJsonPath('data.attributes.avatar', 'default.png');

        $this->getJson(UserRepository::route())
            ->assertOk()
            ->assertJsonPath('data.0.attributes.avatar', 'default.png');
    }

    public function test_image_still_resolves_a_stored_file_to_its_disk_url(): void
    {
        $this->fields(Image::make('avatar')->disk(self::DISK)->default('default.png'));

        $user = $this->userHolding(self::OWN_FILE);

        $this->getJson(UserRepository::route($user))
            ->assertOk()
            ->assertJsonPath('data.attributes.avatar', Storage::disk(self::DISK)->url(self::OWN_FILE));

        $this->getJson(UserRepository::route())
            ->assertOk()
            ->assertJsonPath('data.0.attributes.avatar', Storage::disk(self::DISK)->url(self::OWN_FILE));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function urlResolversWithStoredValuesThatAreNotPaths(): array
    {
        return self::cross(
            ['full url' => ['resolveUsingFullUrl'], 'temporary url' => ['resolveUsingTemporaryUrl']],
            self::storedValuesThatAreNotPaths(),
        );
    }

    #[DataProvider('urlResolversWithStoredValuesThatAreNotPaths')]
    public function test_url_resolvers_never_ask_the_disk_about_a_value_that_is_not_a_path(string $resolver, string $stored): void
    {
        $this->fields(File::make('avatar')->disk(self::DISK)->{$resolver}());

        $user = $this->userHolding($stored);

        $this->getJson(UserRepository::route($user))
            ->assertOk()
            ->assertJsonPath('data.attributes.avatar', $stored === self::REMOTE_URL ? self::REMOTE_URL : null);
    }

    public function test_full_url_resolver_still_resolves_a_stored_file(): void
    {
        $this->fields(File::make('avatar')->disk(self::DISK)->resolveUsingFullUrl());

        $user = $this->userHolding(self::OWN_FILE);

        $this->getJson(UserRepository::route($user))
            ->assertOk()
            ->assertJsonPath('data.attributes.avatar', Storage::disk(self::DISK)->url(self::OWN_FILE));
    }

    public function test_temporary_url_resolver_still_resolves_a_stored_file(): void
    {
        $this->fields(File::make('avatar')->disk(self::DISK)->resolveUsingTemporaryUrl());

        $user = $this->userHolding(self::OWN_FILE);

        $this->assertStringContainsString(
            self::OWN_FILE.'?expiration=',
            $this->getJson(UserRepository::route($user))->assertOk()->json('data.attributes.avatar')
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function pathsOnTheDisk(): array
    {
        return [
            'file name' => ['report.pdf'],
            'nested' => ['documents/2024/report.pdf'],
            'leading slash' => ['/documents/report.pdf'],
            'dots inside a segment' => ['documents/v1..2/report..final.pdf'],
            'dot prefixed segment' => ['documents/..drafts/report.pdf'],
            'spaces' => ['documents/annual report.pdf'],
        ];
    }

    /**
     * Paths that are only classified, never written: some are not valid file names on every filesystem.
     *
     * @return array<string, array{string}>
     */
    public static function otherPathsOnTheDisk(): array
    {
        return [
            'colon after the first segment' => ['documents/2024-01-01T10:00.pdf'],
            'colon after a digit' => ['2024:report.pdf'],
            'colon in a file name' => ['Meeting: notes.pdf'],
            'colon in the first segment' => ['tenant:5/avatar.jpg'],
            'hidden file' => ['.gitignore'],
            'current directory segment' => ['./report.pdf'],
            'trailing dots' => ['documents/report...'],
        ];
    }

    #[DataProvider('pathsOnTheDisk')]
    public function test_field_delete_still_deletes_a_stored_file(string $path): void
    {
        Storage::disk(self::DISK)->put($path, 'content');

        $this->fields(File::make('avatar')->disk(self::DISK));

        $user = $this->userHolding($path);

        $this->deleteJson(UserRepository::route($user->getKey().'/field/avatar'))->assertNoContent();

        $this->assertNull($user->fresh()->avatar);
        Storage::disk(self::DISK)->assertMissing($path);
        $this->assertNoFileWasDeleted();
    }

    #[DataProvider('pathsOnTheDisk')]
    public function test_prunable_upload_still_deletes_the_replaced_file(string $path): void
    {
        Storage::disk(self::DISK)->put($path, 'content');

        $this->fields(File::make('avatar')->disk(self::DISK)->prunable()->storeAs('new.jpg'));

        $user = $this->userHolding($path);

        $this->postJson(UserRepository::route($user), [
            'avatar' => UploadedFile::fake()->image('image.jpg'),
        ])->assertOk();

        $this->assertSame('new.jpg', $user->fresh()->avatar);
        Storage::disk(self::DISK)->assertMissing($path);
        Storage::disk(self::DISK)->assertExists('new.jpg');
        $this->assertNoFileWasDeleted();
    }

    #[DataProvider('pathsOnTheDisk')]
    public function test_deleting_the_record_still_prunes_a_stored_file(string $path): void
    {
        Storage::disk(self::DISK)->put($path, 'content');

        $this->fields(File::make('avatar')->disk(self::DISK)->prunable());

        $user = $this->userHolding($path);

        $this->deleteJson(UserRepository::route($user))->assertNoContent();

        Storage::disk(self::DISK)->assertMissing($path);
        $this->assertNoFileWasDeleted();
    }

    public function test_default_delete_callback_deletes_only_the_paths_of_a_list(): void
    {
        Storage::disk(self::DISK)->put('first.pdf', 'content');
        Storage::disk(self::DISK)->put('second.pdf', 'content');

        $model = new User;
        $model->avatar = ['first.pdf', 'http://example.com/../../other.jpg', 'second.pdf', self::REMOTE_URL];

        $field = File::make('avatar')->disk(self::DISK);

        call_user_func($field->getDeleteCallback(), RestifyRequest::create('/'), $model, self::DISK, null);

        Storage::disk(self::DISK)->assertMissing('first.pdf');
        Storage::disk(self::DISK)->assertMissing('second.pdf');
        $this->assertNoFileWasDeleted();
    }

    public function test_custom_delete_callback_still_receives_the_stored_value(): void
    {
        $received = [];

        $this->fields(
            File::make('avatar')->disk(self::DISK)->delete(function ($request, $model, $disk, $path) use (&$received) {
                $received = [$disk, $path];

                return ['avatar' => null];
            })
        );

        $user = $this->userHolding(self::REMOTE_URL);

        $this->deleteJson(UserRepository::route($user->getKey().'/field/avatar'))->assertNoContent();

        $this->assertSame([self::DISK, self::REMOTE_URL], $received);
        $this->assertNull($user->fresh()->avatar);
    }

    #[DataProvider('remoteUrls')]
    public function test_a_remote_url_is_recognised(string $url): void
    {
        $this->assertTrue(File::isRemoteUrl($url));
        $this->assertFalse(File::isStoragePath($url));
    }

    #[DataProvider('stringsThatAreNotStored')]
    public function test_any_other_string_is_not_a_remote_url(string $input): void
    {
        $this->assertFalse(File::isRemoteUrl($input));
    }

    public function test_a_value_that_is_not_a_string_is_neither_a_remote_url_nor_a_path(): void
    {
        foreach ([null, '', 0, 12, 1.5, true, false, ['avatar.jpg'], new \stdClass] as $value) {
            $this->assertFalse(File::isRemoteUrl($value));
            $this->assertFalse(File::isStoragePath($value));
        }
    }

    #[DataProvider('pathsOnTheDisk')]
    #[DataProvider('otherPathsOnTheDisk')]
    public function test_a_path_on_the_disk_is_recognised(string $path): void
    {
        $this->assertTrue(File::isStoragePath($path));
        $this->assertFalse(File::isRemoteUrl($path));
    }

    #[DataProvider('storedValuesThatAreNotPaths')]
    public function test_a_url_or_a_value_with_a_parent_segment_is_not_a_path_on_the_disk(string $stored): void
    {
        $this->assertFalse(File::isStoragePath($stored));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function otherValuesThatAreNotPaths(): array
    {
        return [
            'parent segment only' => ['..'],
            'leading parent segment' => ['../other.jpg'],
            'trailing parent segment' => ['avatars/..'],
            'absolute with parent segment' => ['/avatars/../other.jpg'],
            'mixed separators' => ['avatars/..\\other.jpg'],
            'ftp url' => ['ftp://example.com/avatar.jpg'],
            'file url' => ['file:///avatar.jpg'],
            'mailto url' => ['mailto:someone@example.com'],
            'stream wrapper' => ['php://filter/resource=avatar.jpg'],
            'uppercase scheme' => ['HTTPS://example.com/avatar.jpg'],
            'scheme with plus, dot and dash' => ['a+b.c-d://example.com/avatar.jpg'],
            'url without a host' => ['https:///avatar.jpg'],
            'url with a space' => ['https://example.com/my avatar.jpg'],
        ];
    }

    #[DataProvider('otherValuesThatAreNotPaths')]
    public function test_other_values_are_not_a_path_on_the_disk(string $stored): void
    {
        $this->assertFalse(File::isStoragePath($stored));
    }

    private function fields(mixed ...$fields): void
    {
        UserRepository::partialMock()->shouldReceive('fields')->andReturn($fields);
    }

    /**
     * A user whose avatar column holds the given value, written straight to the database.
     */
    private function userHolding(?string $stored): User
    {
        $user = User::factory()->create();

        $user->forceFill([
            'avatar' => $stored,
            'avatar_original' => 'original.jpg',
            'avatar_size' => 1024,
        ])->save();

        return $user;
    }

    private function assertNoFileWasDeleted(): void
    {
        Storage::disk(self::DISK)->assertExists(self::OWN_FILE);
        Storage::disk(self::DISK)->assertExists(self::OTHER_FILE);
    }

    /**
     * @param  array<string, array{mixed}>  $first
     * @param  array<string, array{mixed}>  $second
     * @return array<string, array{mixed, mixed}>
     */
    private static function cross(array $first, array $second): array
    {
        $cases = [];

        foreach ($first as $firstName => [$firstValue]) {
            foreach ($second as $secondName => [$secondValue]) {
                $cases["{$firstName}, {$secondName}"] = [$firstValue, $secondValue];
            }
        }

        return $cases;
    }
}
