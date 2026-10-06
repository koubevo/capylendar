import type { EventKindValue } from '@/types/EventKind';

export interface EventFormData {
    title: string;
    capybara: 'blue' | 'pink' | 'yellow';
    date: string;
    start_at: string;
    end_at: string;
    is_all_day: boolean;
    is_private: boolean;
    countdown_enabled: boolean;
    description: string;
    tags: number[];
    image: File | null;
    remove_image: boolean;
    kind: EventKindValue;
    surprise_image: File | null;
    surprise_password: string;
    surprise_hint: string;
    remove_surprise_image: boolean;
}
