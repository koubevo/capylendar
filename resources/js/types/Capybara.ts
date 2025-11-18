export interface Capybara {
    value: 'blue' | 'pink' | 'yellow';
    label: string;
    avatar: Avatar;
    classes: string;
}

interface Avatar {
    src: string;
    alt: string;
}
