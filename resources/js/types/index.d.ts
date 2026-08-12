import type { Capybara } from '@/types/Capybara';
import type { RelationshipMenuSummary } from '@/types/Relationship';
import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    vapidPublicKey?: string;
    version: string;
    relationshipSummary: RelationshipMenuSummary | null;
};

export interface User {
    id: number;
    name: string;
    email: string;
    capybara: Capybara['value'];
    notifications_enabled: boolean;
}

export type BreadcrumbItemType = BreadcrumbItem;
