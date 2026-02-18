export interface TodoFormData {
    title: string;
    capybara: string;
    deadline: string;
    priority: string;
    is_private: boolean;
    description: string;
    tags: number[];
    image: File | null;
    remove_image: boolean;
}
