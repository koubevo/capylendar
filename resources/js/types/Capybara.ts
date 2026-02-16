export interface Capybara {
    value: 'blue' | 'pink' | 'yellow';
    label: string;
    avatar: Avatar;
    classes: string;
    link_classes: string;
}

interface Avatar {
    src: string;
    alt: string;
}
