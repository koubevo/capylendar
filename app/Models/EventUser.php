<?php

namespace App\Models;

use Database\Factories\EventUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class EventUser extends Model
{
    /** @use HasFactory<EventUserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * @var string
     */
    protected $table = 'event_user';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'event_id',
    ];
}
