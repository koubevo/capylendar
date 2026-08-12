<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\RelationshipSettingsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property Carbon|null $started_on
 * @property string|null $name
 * @property bool $notifications_enabled
 * @property int $created_by
 * @property int $updated_by
 */
class RelationshipSettings extends Model
{
    /** @use HasFactory<RelationshipSettingsFactory> */
    use HasFactory;

    public const SINGLETON_ID = 1;

    protected $fillable = [
        'started_on',
        'name',
        'notifications_enabled',
        'created_by',
        'updated_by',
    ];

    public static function current(): ?self
    {
        return self::query()->find(self::SINGLETON_ID);
    }

    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'notifications_enabled' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return HasMany<RelationshipMilestoneDelivery, $this> */
    public function milestoneDeliveries(): HasMany
    {
        return $this->hasMany(RelationshipMilestoneDelivery::class);
    }
}
