import { Capybara } from '@/types/Capybara';

export interface Message {
    id: number;
    content: string;
    created_at_human: string;
    user: MessageUser;
}

interface MessageUser {
    id: number;
    name: string;
    capybara: Capybara;
}
