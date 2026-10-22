<?php

namespace App\Concerns;

use App\Models\Reaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** @property-read Collection<int, Reaction> $reactions */
trait HasReactions
{
    protected static function bootHasReactions(): void
    {
        static::deleted(function (self $item): void {
            $item->reactions()->delete();
        });
    }

    /** @return MorphMany<Reaction, $this> */
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }
}
