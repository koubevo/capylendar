import { User } from '@/types/index';

export interface Document {
    id: number;
    title: string;
    body: string;
    excerpt: string;
    author: User;
    created_at_human: string;
    updated_at_human: string;
}
