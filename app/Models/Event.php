<?php

namespace App\Models;

use App\Enums\Capybara;
use App\Enums\EventKind;
use Carbon\Carbon;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $title
 * @property Carbon $start_at
 * @property Carbon|null $end_at
 * @property bool $is_all_day
 * @property bool $countdown_enabled
 * @property Capybara $capybara
 * @property string|null $description
 * @property bool $is_private
 * @property string|null $image_path
 * @property EventKind $kind
 * @property string|null $surprise_image_path
 * @property string|null $surprise_password
 * @property string|null $surprise_hint
 * @property string|null $surprise_image_url
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property string $created_at_human
 * @property string|null $updated_at_human
 * @property User $author
 * @property array<string, array<string, string>>|null $meta
 * @property array<Tag> $tags
 */
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    use SoftDeletes;

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
        'countdown_enabled',
        'description',
        'icon',
        'meta',
        'image_path',
        'kind',
        'surprise_image_path',
        'surprise_password',
        'surprise_hint',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'surprise_password',
    ];

    /**
     * @var array<string, string>
     */
    protected $attributes = [
        'kind' => EventKind::Standard->value,
    ];

    /**
     * @return array<string, string|Capybara|EventKind>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'is_all_day' => 'boolean',
            'countdown_enabled' => 'boolean',
            'capybara' => Capybara::class,
            'kind' => EventKind::class,
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Event $event) {
            $event->updated_at = null;
        });
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

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'event_tag')->orderBy('label', 'asc');
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isPrivate(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => array_key_exists('subscribers_count', $attributes)
                ? (is_numeric($attributes['subscribers_count']) && (int) $attributes['subscribers_count'] === 1)
                : $this->subscribers()->count() === 1
        );
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function createdAtHuman(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at->diffForHumans()
        );
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function updatedAtHuman(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->updated_at?->diffForHumans()
        );
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path
                ? route('event.image.show', $this).'?v='.($this->updated_at?->timestamp ?? 0) // @phpstan-ignore nullsafe.neverNull
                : null
        );
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function surpriseImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->surprise_image_path
                ? route('event.surprise.show', $this).'?v='.md5($this->surprise_image_path)
                : null
        );
    }
}
