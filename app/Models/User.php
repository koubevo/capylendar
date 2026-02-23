<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Capybara;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use NotificationChannels\WebPush\HasPushSubscriptions;

/**
 * @property int $id
 * @property string $name
 * @property Capybara $capybara
 * @property bool $notifications_enabled
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'notifications_enabled',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'notifications_enabled' => 'boolean',
            'capybara' => Capybara::class,
        ];
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function authoredEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'author_id');
    }

    /**
     * @return BelongsToMany<Event, $this>
     */
    public function assignedEvents(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_user');
    }

    /**
     * @return HasMany<Todo, $this>
     */
    public function authoredTodos(): HasMany
    {
        return $this->hasMany(Todo::class, 'author_id');
    }

    /**
     * @return BelongsToMany<Todo, $this>
     */
    public function assignedTodos(): BelongsToMany
    {
        return $this->belongsToMany(Todo::class, 'todo_user');
    }
}
