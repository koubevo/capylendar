import { computed, type MaybeRefOrGetter, toValue } from 'vue';

export interface LinkSegment {
    type: 'text' | 'link';
    value: string;
}

/**
 * Common TLDs to detect bare domain URLs (e.g. google.com, example.co.uk).
 * Kept intentionally small to avoid false positives.
 */
const TLDS =
    'com|cz|sk|org|net|io|dev|eu|co|me|info|biz|app|xyz|site|online|store|tech|ai|gg|tv|fm|ly|to|cc|uk|de|fr|pl|at|ch|nl|be|se|no|fi|dk|it|es|pt|ru|ua|jp|kr|cn|tw|au|nz|ca|us|br|mx|ar|cl|in|za|ke|ng|il';

/**
 * Matches URLs in these forms:
 * 1. http:// or https:// followed by non-whitespace
 * 2. www. followed by domain-like text
 * 3. Bare domains: word.tld (with optional path/query)
 */
const URL_REGEX = new RegExp(
    '(?:https?:\\/\\/[^\\s<>]+' +
        '|www\\.[^\\s<>]+' +
        '|(?:[a-zA-Z0-9-]+\\.)+(?:' +
        TLDS +
        ')(?:\\/[^\\s<>]*)?)',
    'gi',
);

/**
 * Returns true if the text contains at least one URL.
 */
export function hasLinks(text: string): boolean {
    // Create a fresh regex to avoid lastIndex state issues with the global flag
    const regex = new RegExp(URL_REGEX.source, URL_REGEX.flags);
    return regex.test(text);
}

/**
 * Returns true if the text contains only URLs and whitespace, with no other content.
 */
export function isOnlyLinks(text: string): boolean {
    if (!text.trim()) return false;
    const withoutUrls = text.replace(
        new RegExp(URL_REGEX.source, URL_REGEX.flags),
        '',
    );
    return withoutUrls.trim().length === 0;
}

/**
 * Splits a string into text and link segments for safe rendering.
 * No v-html needed — render with v-for using <a> or <span>.
 */
export function linkify(text: string): LinkSegment[] {
    if (!text) return [{ type: 'text', value: '' }];

    const segments: LinkSegment[] = [];
    let lastIndex = 0;

    for (const match of text.matchAll(URL_REGEX)) {
        const matchIndex = match.index;
        const matchText = match[0];

        if (matchIndex > lastIndex) {
            segments.push({
                type: 'text',
                value: text.slice(lastIndex, matchIndex),
            });
        }

        // Strip trailing punctuation that's likely not part of the URL,
        // but preserve balanced parentheses (e.g. Wikipedia URLs)
        let cleaned = matchText.replace(/[.,;:!?]+$/, '');

        // Only strip trailing ')' if parens are unbalanced
        while (cleaned.endsWith(')')) {
            const open = (cleaned.match(/\(/g) || []).length;
            const close = (cleaned.match(/\)/g) || []).length;
            if (close > open) {
                cleaned = cleaned.slice(0, -1);
            } else {
                break;
            }
        }

        const trailingChars = matchText.slice(cleaned.length);

        segments.push({ type: 'link', value: cleaned });

        if (trailingChars) {
            segments.push({ type: 'text', value: trailingChars });
        }

        lastIndex = matchIndex + matchText.length;
    }

    if (lastIndex < text.length) {
        segments.push({ type: 'text', value: text.slice(lastIndex) });
    }

    return segments.length > 0 ? segments : [{ type: 'text', value: text }];
}

/**
 * Extracts the display domain from a URL string.
 */
export function extractDomain(url: string): string {
    try {
        const href = toLinkHref(url);
        const hostname = new URL(href).hostname;
        return hostname.replace(/^www\./, '');
    } catch {
        return url;
    }
}

/**
 * Returns the full href for a link segment.
 * Prepends https:// if no protocol is present.
 */
export function toLinkHref(url: string): string {
    if (url.startsWith('http://') || url.startsWith('https://')) {
        return url;
    }
    return `https://${url}`;
}

/**
 * Reactive composable for use in Vue components.
 */
export function useLinkify(text: MaybeRefOrGetter<string>) {
    return computed(() => linkify(toValue(text)));
}
