import { Capybara } from '@/types/Capybara';
import { User } from '@/types/index';
import { Tag } from '@/types/Tag';

export interface Event {
    id: number;
    title: string;
    date: EventDate;
    capybara: Capybara;
    is_private: boolean;
    description?: string;
    description_without_meta?: string;
    has_hearts: boolean;
    created_at_human: string;
    updated_at_human?: string;
    author: User;
    meta?: {
        map_preview?: {
            title: string;
            image: string;
            url: string;
        };
    };
    has_map_meta: boolean;
    image_url?: string;
    tags?: Tag[];
}

export interface EventDate {
    key: string;
    label: string;
    start_time: string;
    end_time: string;
    is_all_day: boolean;
}

export type View = 'list' | 'detail';
