import { Capybara } from '@/types/Capybara';

export interface Event {
    id: number;
    title: string;
    date: string;
    start_at: string;
    end_at: ?string;
    is_all_day: boolean;
    capybara: Capybara;
}
