<?php

namespace App\Models;

use Database\Factories\EventUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EventUser extends Pivot
{
    /** @use HasFactory<EventUserFactory> */
    use HasFactory;

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
