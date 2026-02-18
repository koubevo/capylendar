export interface EventFilters {
    search?: string | null;
    capybara?: 'blue' | 'pink' | 'yellow' | null;
    tags?: number[] | null;
    [key: string]: any;
}
