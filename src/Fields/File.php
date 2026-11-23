<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Contracts\Deletable as DeletableContract;
use Binaryk\LaravelRestify\Contracts\FileStorable as StorableContract;
use Binaryk\LaravelRestify\Fields\Concerns\AcceptsTypes;
use Binaryk\LaravelRestify\Fields\Concerns\Deletable;
use Binaryk\LaravelRestify\Fields\Concerns\FileStorable;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Storable;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class File extends Field implements DeletableContract, StorableContract
{
    use AcceptsTypes;
    use Deletable;
    use FileStorable;

    /**
     * The callback that should be used to determine the file's storage name.
     *
     * @var callable|null
     */
    public $storeAs;

    /**
     * The column where the file's original name should be stored.
     *
     * @var string
     */
    public $originalNameColumn;

    /**
     * The column where the file's size should be stored.
     *
     * @var string
     */
    public $sizeColumn;

    /**
     * The custom filename resolved from storeAs callback.
     *
     * @var string|null
     */
    protected $customFilename;

    /**
     * Whether to use the custom filename for the original name column.
     *
     * @var bool
     */
    protected $useCustomFilenameForOriginal = false;

    /**
     * The callback that should be executed to store the file.
     *
     * @var callable|Storable
     */
    public $storageCallback;

    /**
     * The bulk row being filled. It is null on a single store or update, and outside of a fill.
     */
    protected ?int $bulkRow = null;

    public function __construct($attribute, ?callable $resolveCallback = null)
    {
        parent::__construct($attribute, $resolveCallback);

        $this->prepareStorageCallback();

        $this->delete(function ($request, $model, $disk, $path) {
            if ($file = $this->value ?? $model->{$this->attribute}) {
                $this->deleteFromStorage($file);

                return $this->columnsThatShouldBeDeleted();
            }
        });
    }

    /**
     * Determine if a request value is a remote URL the field stores as it is, instead of an upload.
     *
     * Only an absolute http or https URL with a host and without a ".." segment qualifies.
     *
     * @phpstan-assert-if-true =non-empty-string $value
     */
    public static function isRemoteUrl(mixed $value): bool
    {
        if (! is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)
            && ! empty(parse_url($value, PHP_URL_HOST))
            && ! static::hasParentSegment($value);
    }

    /**
     * Determine if a stored value is a path on the field's disk.
     *
     * The column can also hold a remote URL, or a value that got there some other way. The field stored
     * no file for those, and a filesystem resolves their ".." segments to the path of another file, so
     * a URL or a value with a ".." segment is never handed to the disk.
     *
     * @phpstan-assert-if-true =non-empty-string $value
     */
    public static function isStoragePath(mixed $value): bool
    {
        return is_string($value)
            && $value !== ''
            && ! static::isUrl($value)
            && ! static::hasParentSegment($value);
    }

    /**
     * Determine if the value is a URL of any scheme: "scheme://...", or whatever URL input has ever stored.
     */
    protected static function isUrl(string $value): bool
    {
        return preg_match('~^[a-z][a-z0-9+.\-]*://~i', $value) === 1
            || filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Determine if the value has a ".." segment, with either directory separator.
     */
    protected static function hasParentSegment(string $value): bool
    {
        return in_array('..', explode('/', str_replace('\\', '/', $value)), true);
    }

    /**
     * Delete the stored value from the field's disk, leaving out whatever is not a path on it.
     */
    protected function deleteFromStorage(mixed $stored): void
    {
        $paths = array_values(array_filter(Arr::wrap($stored), fn (mixed $path): bool => static::isStoragePath($path)));

        if ($paths !== []) {
            Storage::disk($this->getStorageDisk())->delete($paths);
        }
    }

    /**
     * Specify the callback or the name that should be used to determine the file's storage name.
     *
     * @param  callable|string  $storeAsCallback
     * @return $this
     */
    public function storeAs($storeAs): self
    {
        $this->storeAs = $storeAs;

        return $this;
    }

    /**
     * Resolve a temporary URL for s3 compatible disks.
     *
     * @return $this
     */
    public function resolveUsingTemporaryUrl(
        bool $resolveTemporaryUrl = true,
        ?CarbonInterface $expiration = null,
        array $options = []
    ): self {
        if (! $resolveTemporaryUrl) {
            return $this;
        }

        $callback = function ($value) use ($expiration) {
            if (! $value) {
                return;
            }

            if (! static::isStoragePath($value)) {
                return static::isRemoteUrl($value) ? $value : null;
            }

            return Storage::disk($this->getStorageDisk())->temporaryUrl(
                $value,
                $expiration ?? now()->addMinutes(5)
            );
        };

        $this->resolveCallback($callback);

        return $this;
    }

    /**
     * Resolve a full path for the file.
     *
     * @return $this
     */
    public function resolveUsingFullUrl(): self
    {
        $callback = function ($value) {
            if (! $value) {
                return;
            }

            if (! static::isStoragePath($value)) {
                return static::isRemoteUrl($value) ? $value : null;
            }

            return Storage::disk($this->getStorageDisk())->url($value);
        };

        $this->resolveCallback($callback);

        return $this;
    }

    /**
     * Prepare the storage callback.
     */
    protected function prepareStorageCallback(?callable $storageCallback = null): void
    {
        $this->storageCallback = $storageCallback ?? function ($request, $model) {
            return $this->mergeExtraStorageColumns($request, [
                $this->attribute => $this->storeFile($request, $this->requestAttribute()),
            ]);
        };
    }

    /**
     * The key the field's input has in the request: the attribute, or "{row}.{attribute}" while a bulk row
     * is filled, when the request holds that row alone, keyed by its index.
     */
    protected function requestAttribute(): string
    {
        $attribute = is_string($this->attribute) ? $this->attribute : '';

        return $this->bulkRow === null ? $attribute : "{$this->bulkRow}.{$attribute}";
    }

    /**
     * The bulk row as the last argument of a callback. A single store or update passes nothing more than
     * it always did.
     *
     * @return list<int>
     */
    protected function bulkRowArgument(): array
    {
        return $this->bulkRow === null ? [] : [$this->bulkRow];
    }

    /**
     * Specify the column where the file's original name should be stored.
     *
     * @param  string  $column
     * @return $this
     */
    public function storeOriginalName($column)
    {
        $this->originalNameColumn = $column;

        return $this;
    }

    /**
     * Specify the column where the file size should be stored.
     *
     * @param  string  $column
     * @return $this
     */
    public function storeSize($column)
    {
        $this->sizeColumn = $column;

        return $this;
    }

    protected function resolveFileFromRequest(Request $request): ?UploadedFile
    {
        $attribute = $this->requestAttribute();

        if (($file = $request->input($attribute)) instanceof UploadedFile && $file->isValid()) {
            return $file;
        }

        if (($file = $request->file($attribute)) && $file->isValid()) {
            return $file;
        }

        return null;
    }

    protected function storeFile(Request $request, string $requestAttribute)
    {
        $file = $this->resolveFileFromRequest($request);

        if (! $file) {
            throw new RuntimeException("No valid file found in the request for attribute {$requestAttribute}");
        }

        if (! $this->storeAs) {
            $this->customFilename = null;
            $this->useCustomFilenameForOriginal = false;

            return $file->store($this->getStorageDir(), $this->getStorageDisk());
        }

        $isCallable = is_callable($this->storeAs);
        $filename = $isCallable
            ? call_user_func($this->storeAs, $request, ...$this->bulkRowArgument())
            : $this->storeAs;

        // If storeAs returns empty/null, fallback to auto-generated name
        if (empty($filename)) {
            $this->customFilename = null;
            $this->useCustomFilenameForOriginal = false;

            return $file->store($this->getStorageDir(), $this->getStorageDisk());
        }

        // Smart extension handling - append if missing
        $extension = $file->getClientOriginalExtension();
        if ($extension && ! str_ends_with(strtolower($filename), '.'.$extension)) {
            $filename = $filename.'.'.$extension;
        }

        $this->customFilename = $filename;
        // Only use custom filename for original name if it came from a callable
        $this->useCustomFilenameForOriginal = $isCallable;

        return $file->storeAs(
            $this->getStorageDir(),
            $filename,
            $this->getStorageDisk()
        );
    }

    /**
     * Specify the callback that should be used to store the file.
     *
     * @param  callable|Storable  $storageCallback
     * @return $this
     */
    public function store($storageCallback): self
    {
        $this->storageCallback = is_subclass_of($storageCallback, Storable::class)
            ? [app($storageCallback), 'handle']
            : $storageCallback;

        return $this;
    }

    /**
     * Merge the specified extra file information columns into the storable attributes.
     *
     * @param  Request  $request
     */
    protected function mergeExtraStorageColumns($request, array $attributes): array
    {
        $file = $this->resolveFileFromRequest($request);

        if ($this->originalNameColumn) {
            $attributes[$this->originalNameColumn] = ($this->useCustomFilenameForOriginal && $this->customFilename)
                ? $this->customFilename
                : $file->getClientOriginalName();
        }

        if ($this->sizeColumn) {
            $attributes[$this->sizeColumn] = $file->getSize();
        }

        return $attributes;
    }

    /**
     * Get an array of the columns that should be deleted and their values.
     */
    protected function columnsThatShouldBeDeleted(): array
    {
        $attributes = [$this->attribute => null];

        if ($this->originalNameColumn) {
            $attributes[$this->originalNameColumn] = null;
        }

        if ($this->sizeColumn) {
            $attributes[$this->sizeColumn] = null;
        }

        return $attributes;
    }

    /**
     * Fill the model from the request. On a bulk store or bulk update the request holds one row, and the
     * field reads that row's input.
     */
    public function fillAttribute(RestifyRequest $request, $model, ?int $bulkRow = null)
    {
        $this->bulkRow = $bulkRow;

        try {
            return $this->fillFileAttribute($request, $model);
        } finally {
            $this->bulkRow = null;
        }
    }

    /**
     * Store the upload or the URL the request holds under {@see self::requestAttribute()}.
     *
     * @param  mixed  $model
     * @return mixed
     */
    protected function fillFileAttribute(RestifyRequest $request, $model)
    {
        if ($this->storeCallback instanceof Closure) {
            return call_user_func($this->storeCallback, $request, $model, $this->attribute, ...$this->bulkRowArgument());
        }

        $requestAttribute = $this->requestAttribute();

        // Handle URL input first. Any other string is not stored: it falls through to the upload handling.
        if ($request->has($requestAttribute) && is_string($request->input($requestAttribute))) {
            $url = $request->input($requestAttribute);

            if (static::isRemoteUrl($url)) {
                if ($this->isPrunable()) {
                    call_user_func(
                        $this->deleteCallback,
                        $request,
                        $model,
                        $this->getStorageDisk(),
                        $this->getStoragePath()
                    );
                }

                $model->{$this->attribute} = $url;

                if ($this->originalNameColumn) {
                    $model->{$this->originalNameColumn} = basename($url);
                }

                return $this;
            }
        }

        if (! $this->resolveFileFromRequest($request)) {
            return $this;
        }

        if ($this->isPrunable()) {
            call_user_func(
                $this->deleteCallback,
                $request,
                $model,
                $this->getStorageDisk(),
                $this->getStoragePath()
            );
        }

        $result = call_user_func(
            $this->storageCallback,
            $request,
            $model,
            $this->attribute,
            $this->disk,
            $this->storagePath,
            ...$this->bulkRowArgument()
        );

        if ($result === true) {
            return $this;
        }

        if ($result instanceof Closure) {
            return $result;
        }

        if (! is_array($result)) {
            return $model->{$this->attribute} = $result;
        }

        foreach ($result as $key => $value) {
            if ($model->isFillable($key)) {
                $model->{$key} = $value;
            }
        }

        return $this;
    }

    public function getStoringRules(): array
    {
        $rules = parent::getStoringRules();

        // Modify validation to accept URLs
        foreach ($rules as &$rule) {
            if (is_string($rule) && str($rule)->startsWith('file')) {
                $rule = 'sometimes|'.$rule;
            }
        }

        return $rules;
    }

    /**
     * Get the full path that the field is stored at on disk.
     *
     * @return string|null
     */
    public function getStoragePath()
    {
        return $this->value;
    }
}
