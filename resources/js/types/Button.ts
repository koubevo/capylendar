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

export interface DeleteAction {
    title: string;
    url: string;
    icon?: string;
}
