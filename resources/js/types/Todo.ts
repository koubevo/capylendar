import { Capybara } from '@/types/Capybara';
import { User } from '@/types/index';
import { Tag } from '@/types/Tag';

export interface TodoPriority {
    value: string;
    label: string;
    icon: string;
    border_class: string;
    icon_color: string;
}

export interface TodoDeadline {
    key: string;
    label: string;
}

export interface Todo {
    id: number;
    title: string;
    deadline: TodoDeadline;
    capybara: Capybara;
    priority: TodoPriority;
    is_private: boolean;
    is_finished: boolean;
    finished_at?: string;
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
