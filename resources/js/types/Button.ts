export interface Button {
    to: To;
    label: string;
}

export interface To {
    url: string;
    method: string;
}

export interface DuplicateAction {
    url: string;
    icon?: string;
}

export interface EditAction {
    url: string;
    icon?: string;
}

export interface Action {
    title: string;
    url: string;
    method?: 'post' | 'delete' | 'put' | 'patch';
    icon?: {
        name: string;
        class: string;
    };
    titleShort?: string;
}

export type EventAction = Action;
