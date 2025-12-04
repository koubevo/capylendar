<?php

namespace App\Models;

use App\Enums\Capybara;
use Carbon\Carbon;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $title
 * @property Carbon $start_at
 * @property Carbon|null $end_at
 * @property bool $is_all_day
 * @property Capybara $capybara
 * @property string|null $description
 * @property bool $is_private
 */
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'author_id',
        'capybara',
        'start_at',
        'end_at',
        'is_all_day',
        'description',
        'icon',
    ];

    /**
     * @return array<string, string|Capybara>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_all_day' => 'boolean',
            'capybara' => Capybara::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_user');
    }

    public function isPrivate(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => ($this->subscribers()->count() === 1)
        );
    }
}
