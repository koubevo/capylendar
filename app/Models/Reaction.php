<?php

namespace App\Models;

use App\Enums\ReactionType;
use Database\Factories\ReactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $user_id
 * @property ReactionType $key
 * @property User $user
 */
class Reaction extends Model
{
    /** @use HasFactory<ReactionFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['user_id', 'key'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['key' => ReactionType::class];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphTo<Model, $this> */
    public function reactable(): MorphTo
    {
        return $this->morphTo();
    }
}
