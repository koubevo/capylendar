import type { Capybara } from '@/types/Capybara';

export type ReactionKey =
    'approve' | 'seen' | 'love' | 'celebrate' | 'question';

export interface ReactionUser {
    readonly id: number;
    readonly name: string;
    readonly capybara: Capybara['value'];
}

export interface ReactionGroup {
    readonly key: ReactionKey;
    readonly users: readonly ReactionUser[];
}

export interface ReactionOption {
    key: ReactionKey;
    emoji: string;
    label: string;
}
