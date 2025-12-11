import { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function urlIsActive(
    urlToCheck: NonNullable<InertiaLinkProps['href']>,
    currentUrl: string,
) {
    return toUrl(urlToCheck) === currentUrl;
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

export const GOOGLE_MAPS_REGEX =
    /https?:\/\/(?:www\.)?(?:google\.(?:com|cz)\/maps\/[^\s]+|maps\.app\.goo\.gl\/[^\s]+)/;

export function hasGoogleMapUrl(text: string | null | undefined): boolean {
    if (!text) return false;
    return GOOGLE_MAPS_REGEX.test(text);
}
