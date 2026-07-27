export interface EventFilters {
    [key: string]: string | number[] | null | undefined;

    search?: string | null;
    capybara?: 'blue' | 'pink' | 'yellow' | null;
    tags?: number[] | null;
}
