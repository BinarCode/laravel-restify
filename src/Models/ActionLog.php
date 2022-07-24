<?php

namespace Binaryk\LaravelRestify\Models;

use Binaryk\LaravelRestify\Actions\Action;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class ActionLog.
 *
 * @property int $id
 * @property string $batch_id
 * @property string $user_id
 * @property string $name
 * @property string $actionable_type // the actual model
 * @property string $actionable_id
 * @property string $target_type // say we attach model A to model B (current one) - the model A will be the target
 * @property string $target_id
 * @property string $model_type // say we detach model A from model B (current one) - the pivot will be the model_type
 * @property string $model_id
 * @property string $fields
 * @property string $status
 * @property string $original
 * @property string $changes
 * @property string $exception
 * @property ?array $meta
 */
class ActionLog extends Model
{
    protected $table = 'action_logs';

    protected $guarded = [];

    protected $casts = [
        'original' => 'array',
        'changes' => 'array',
        'meta' => 'array',
    ];

    public const STATUS_FINISHED = 'finished';

    public const ACTION_CREATED = 'Stored';

    public const ACTION_UPDATED = 'Updated';

    public const ACTION_DELETED = 'Deleted';

    public static function forRepositoryStored(Model $model, Authenticatable $user = null, array $dirty = null): self
    {
        return new static([
            'batch_id' => (string) Str::uuid(),
            'user_id' => optional($user)->getAuthIdentifier(),
            'name' => static::ACTION_CREATED,
            'actionable_type' => $model->getMorphClass(),
            'actionable_id' => $model->getKey(),
            'target_type' => $model->getMorphClass(),
            'target_id' => $model->getKey(),
            'model_type' => $model->getMorphClass(),
            'model_id' => $model->getKey(),
            'fields' => '',
            'status' => static::STATUS_FINISHED,
            'original' => '',
            'changes' => $dirty ?? $model->getDirty(),
            'exception' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function forRepositoryUpdated(Model $model, Authenticatable $user = null): self
    {
        return new static([
            'batch_id' => (string) Str::uuid(),
            'user_id' => optional($user)->getAuthIdentifier(),
            'name' => static::ACTION_UPDATED,
            'actionable_type' => $model->getMorphClass(),
            'actionable_id' => $model->getKey(),
            'target_type' => $model->getMorphClass(),
            'target_id' => $model->getKey(),
            'model_type' => $model->getMorphClass(),
            'model_id' => $model->getKey(),
            'fields' => '',
            'status' => static::STATUS_FINISHED,
            'original' => array_intersect_key($model->getOriginal(), $model->getDirty()),
            'changes' => $model->getDirty(),
            'exception' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function forRepositoryDestroy(Model $model, Authenticatable $user = null): self
    {
        return new static([
            'batch_id' => (string) Str::uuid(),
            'user_id' => optional($user)->getAuthIdentifier(),
            'name' => static::ACTION_DELETED,
            'actionable_type' => $model->getMorphClass(),
            'actionable_id' => $model->getKey(),
            'target_type' => $model->getMorphClass(),
            'target_id' => $model->getKey(),
            'model_type' => $model->getMorphClass(),
            'model_id' => $model->getKey(),
            'fields' => '',
            'status' => static::STATUS_FINISHED,
            'original' => $model->toArray(),
            'changes' => null,
            'exception' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function forRepositoryAction(Action $action, Model $model, Authenticatable $user = null): self
    {
        return new static([
            'batch_id' => (string) Str::uuid(),
            'user_id' => optional($user)->getAuthIdentifier(),
            'name' => $action->uriKey(),
            'actionable_type' => $model->getMorphClass(),
            'actionable_id' => $model->getKey(),
            'target_type' => $model->getMorphClass(),
            'target_id' => $model->getKey(),
            'model_type' => $model->getMorphClass(),
            'model_id' => $model->getKey(),
            'fields' => '',
            'status' => static::STATUS_FINISHED,
            'original' => array_intersect_key($model->getOriginal(), $model->getDirty()),
            'changes' => $model->getDirty(),
            'exception' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function register(
        string $name,
        Model $actionable,
        array $attributes = [],
        ?Authenticatable $user = null
    ): self {
        return new static(array_merge([
            'batch_id' => (string) Str::uuid(),
            'user_id' => optional($user)->getAuthIdentifier(),
            'name' => $name,
            'actionable_type' => $actionable->getMorphClass(),
            'actionable_id' => $actionable->getKey(),
            'status' => static::STATUS_FINISHED,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    public function withMeta(string $key, mixed $value): self
    {
        $this->meta = array_merge($this->meta ?: [], [
            $key => $value,
        ]);

        return $this;
    }

    public function withMetas(array $metas): self
    {
        $this->meta = $metas;

        return $this;
    }

    public function actor(Model $model): self
    {
        $this->user_id = $model->getKey();

        return $this;
    }

    public function target(Model $model): self
    {
        $this->target_id = $model->getKey();
        $this->target_type = $model->getMorphClass();

        return $this;
    }
}
