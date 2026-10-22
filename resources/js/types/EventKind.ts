export type EventKindValue = 'standard' | 'birthday';

export interface EventKind {
    value: EventKindValue;
    label: string;
    icon: string;
    allows_surprise: boolean;
}

export interface EventSurprise {
    hint: string | null;
    is_unlocked: boolean;
    image_url: string | null;
}
