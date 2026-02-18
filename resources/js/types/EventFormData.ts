export interface EventFormData {
    title: string;
    capybara: 'blue' | 'pink' | 'yellow';
    date: string;
    start_at: string;
    end_at: string;
    is_all_day: boolean;
    is_private: boolean;
    description: string;
    tags: number[];
    image: File | null;
    remove_image: boolean;
}
