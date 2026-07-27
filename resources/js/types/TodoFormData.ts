export interface TodoFormData {
    title: string;
    capybara: 'blue' | 'pink' | 'yellow';
    deadline: string;
    priority: string;
    is_private: boolean;
    description: string;
    tags: number[];
}
