<?php

namespace App\Models;

use Database\Factories\RelationshipMilestoneDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelationshipMilestoneDelivery extends Model
{
    /** @use HasFactory<RelationshipMilestoneDeliveryFactory> */
    use HasFactory;

    protected $fillable = [
        'relationship_settings_id',
        'user_id',
        'milestone_key',
        'milestone_on',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'milestone_on' => 'date',
            'delivered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<RelationshipSettings, $this> */
    public function relationshipSettings(): BelongsTo
    {
        return $this->belongsTo(RelationshipSettings::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
