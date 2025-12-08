import { Capybara } from '@/types/Capybara';
import { User } from '@/types/index';

export interface Event {
    id: number;
    title: string;
    date: EventDate;
    capybara: Capybara;
    is_private: boolean;
    description?: string;
    has_hearts: boolean;
    created_at_human: string;
    updated_at_human?: string;
    author: User;
}

export interface EventDate {
    key: string;
    label: string;
    start_time: string;
    end_time: string;
    is_all_day: boolean;
}

export interface View {
    value: 'list' | 'detail';
}
