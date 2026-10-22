<?php

namespace App\Enums;

enum ReactionType: string
{
    case Love = 'love';

    case Celebrate = 'celebrate';

    case Seen = 'seen';

    case Approve = 'approve';

    case Question = 'question';

    /** @return array{key: string, emoji: string, label: string} */
    public function option(): array
    {
        [$emoji, $label] = match ($this) {
            self::Love => ['❤️', 'Líbí se mi'],
            self::Celebrate => ['🤩', 'Těším se'],
            self::Seen => ['👀', 'Viděl/a jsem'],
            self::Approve => ['🙏', 'Díky'],
            self::Question => ['🤔', 'Otázka / nejistota'],
        };

        return ['key' => $this->value, 'emoji' => $emoji, 'label' => $label];
    }

    /** @return list<array{key: string, emoji: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $type): array => $type->option(), self::cases());
    }
}
