<?php

namespace App\Models;

use App\Enums\Capybara;
use App\Enums\Priority;
use Carbon\Carbon;
use Database\Factories\TodoFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $title
 * @property Carbon $deadline
 * @property Capybara $capybara
 * @property Priority $priority
 * @property string|null $description
 * @property bool $is_private
 * @property string|null $image_path
 * @property Carbon|null $finished_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property string $created_at_human
 * @property string|null $updated_at_human
 * @property bool $is_finished
 * @property User $author
 * @property array<string, array<string, string>>|null $meta
 * @property array<Tag> $tags
 */
class Todo extends Model
{
    /** @use HasFactory<TodoFactory> */
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
        'deadline',
        'description',
        'priority',
        'finished_at',
        'image_path',
        'meta',
    ];

    /**
     * @return array<string, string|Capybara|Priority>
     */
    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'finished_at' => 'datetime',
            'capybara' => Capybara::class,
            'priority' => Priority::class,
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Todo $todo) {
            $todo->updated_at = null;
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
        return $this->belongsToMany(User::class, 'todo_user');
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'todo_tag')->orderBy('label', 'asc');
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isPrivate(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => array_key_exists('subscribers_count', $attributes)
                ? $attributes['subscribers_count'] === 1
                : $this->subscribers()->count() === 1
        );
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isFinished(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->finished_at !== null
        );
    }

    /**
     * @return Attribute<string, never>
     */
    protected function createdAtHuman(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at->diffForHumans()
        );
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function updatedAtHuman(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->updated_at?->diffForHumans()
        );
    }
}
