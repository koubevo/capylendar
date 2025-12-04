import { Capybara } from '@/types/Capybara';

export interface Event {
    id: number;
    title: string;
    date: EventDate;
    capybara: Capybara;
    is_private: boolean;
    description?: string;
}

export interface EventDate {
    key: string;
    label: string;
    start_time: string;
    end_time: string;
    is_all_day: boolean;
}
