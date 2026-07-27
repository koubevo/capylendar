<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\WatchPairingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $device_name
 * @property string $device_code_hash
 * @property string $user_code_hash
 * @property Carbon $expires_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $claimed_at
 * @property string|null $claimed_token
 * @property User|null $user
 */
class WatchPairing extends Model
{
    /** @use HasFactory<WatchPairingFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'device_name',
        'device_code_hash',
        'user_code_hash',
        'expires_at',
        'approved_at',
        'claimed_at',
        'claimed_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'approved_at' => 'datetime',
            'claimed_at' => 'datetime',
            'claimed_token' => 'encrypted',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
