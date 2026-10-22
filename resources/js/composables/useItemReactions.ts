import {
    destroy as destroyEventReaction,
    store as storeEventReaction,
} from '@/actions/App/Http/Controllers/EventReactionController';
import {
    destroy as destroyTodoReaction,
    store as storeTodoReaction,
} from '@/actions/App/Http/Controllers/TodoReactionController';
import type {
    ReactionGroup,
    ReactionKey,
    ReactionUser,
} from '@/types/Reaction';
import {
    computed,
    hasInjectionContext,
    inject,
    provide,
    readonly,
    ref,
    toValue,
    watch,
    type InjectionKey,
    type MaybeRefOrGetter,
    type Ref,
} from 'vue';

export type ReactionItemType = 'event' | 'todo';

export interface UseItemReactionsOptions {
    itemType: ReactionItemType;
    itemId: number;
    reactions: MaybeRefOrGetter<ReactionGroup[] | undefined>;
    currentUser: MaybeRefOrGetter<ReactionUser | null>;
}

interface ItemReactionState {
    source: ReactionGroup[] | undefined;
    reactions: Ref<ReactionGroup[]>;
    pendingKeys: Ref<Set<ReactionKey>>;
    errors: Ref<Partial<Record<ReactionKey, string>>>;
}

const itemReactionStates: InjectionKey<Map<string, ItemReactionState>> =
    Symbol('itemReactionStates');

export function provideItemReactions(): void {
    provide(itemReactionStates, new Map());
}

export const reactionKeys: readonly ReactionKey[] = [
    'love',
    'celebrate',
    'seen',
    'approve',
    'question',
];

const reactionError = 'Reakci se nepodařilo uložit. Zkus to prosím znovu.';

function isReactionKey(value: unknown): value is ReactionKey {
    return (
        typeof value === 'string' && reactionKeys.includes(value as ReactionKey)
    );
}

function cloneUser(user: ReactionUser): ReactionUser {
    return { ...user };
}

function cloneGroup(
    group: ReactionGroup | undefined,
): ReactionGroup | undefined {
    return group
        ? { key: group.key, users: group.users.map(cloneUser) }
        : undefined;
}

function isReactionUser(value: unknown): value is ReactionUser {
    if (!value || typeof value !== 'object') {
        return false;
    }

    const user = value as Record<string, unknown>;

    return (
        typeof user.id === 'number' &&
        typeof user.name === 'string' &&
        typeof user.capybara === 'string'
    );
}

function isReactionGroup(value: unknown): value is ReactionGroup {
    if (!value || typeof value !== 'object') {
        return false;
    }

    const group = value as Record<string, unknown>;

    return (
        isReactionKey(group.key) &&
        Array.isArray(group.users) &&
        group.users.every(isReactionUser)
    );
}

function normalizeGroups(value: unknown): ReactionGroup[] {
    if (!Array.isArray(value)) {
        return [];
    }

    const groups: ReactionGroup[] = [];

    for (const candidate of value) {
        if (!isReactionGroup(candidate)) {
            continue;
        }

        if (
            candidate.users.length === 0 ||
            groups.some((item) => item.key === candidate.key)
        ) {
            continue;
        }

        groups.push({
            key: candidate.key,
            users: candidate.users.map(cloneUser),
        });
    }

    return groups;
}

function replaceGroup(
    groups: ReactionGroup[],
    key: ReactionKey,
    group: ReactionGroup | undefined,
): ReactionGroup[] {
    const index = groups.findIndex((item) => item.key === key);

    if (!group || group.users.length === 0) {
        return index === -1
            ? [...groups]
            : groups.filter((item) => item.key !== key);
    }

    const nextGroup = cloneGroup(group);
    if (!nextGroup) {
        return [...groups];
    }

    if (index === -1) {
        return [...groups, nextGroup];
    }

    return groups.map((item, itemIndex) =>
        itemIndex === index ? nextGroup : item,
    );
}

function mergeIncomingGroups(
    currentGroups: ReactionGroup[],
    incomingGroups: ReactionGroup[],
    pendingKeys: ReadonlySet<ReactionKey>,
): ReactionGroup[] {
    return reactionKeys.reduce(
        (groups, key) => {
            if (pendingKeys.has(key)) {
                return groups;
            }

            return replaceGroup(
                groups,
                key,
                incomingGroups.find((group) => group.key === key),
            );
        },
        [...currentGroups],
    );
}

function getCsrfToken(): string {
    if (typeof document === 'undefined') {
        return '';
    }

    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

function responseGroup(
    payload: unknown,
    key: ReactionKey,
): ReactionGroup | undefined {
    if (!payload || typeof payload !== 'object') {
        throw new Error('Invalid reaction response');
    }

    const rawReactions = (payload as { reactions?: unknown }).reactions;
    if (!Array.isArray(rawReactions) || !rawReactions.every(isReactionGroup)) {
        throw new Error('Invalid reaction response');
    }

    const reactions = normalizeGroups(rawReactions);

    return reactions.find((group) => group.key === key);
}

export function useItemReactions(options: UseItemReactionsOptions) {
    const states = hasInjectionContext()
        ? inject(itemReactionStates, null)
        : null;
    const itemKey = `${options.itemType}:${options.itemId}`;
    const source = toValue(options.reactions);
    const state = states?.get(itemKey) ?? {
        source,
        reactions: ref<ReactionGroup[]>(normalizeGroups(source)),
        pendingKeys: ref<Set<ReactionKey>>(new Set()),
        errors: ref<Partial<Record<ReactionKey, string>>>({}),
    };
    states?.set(itemKey, state);
    const { reactions, pendingKeys, errors } = state;

    if (state.source !== source) {
        reactions.value = mergeIncomingGroups(
            reactions.value,
            normalizeGroups(source),
            pendingKeys.value,
        );
        state.source = source;
    }

    const setPending = (key: ReactionKey, pending: boolean): void => {
        const next = new Set(pendingKeys.value);

        if (pending) {
            next.add(key);
        } else {
            next.delete(key);
        }

        pendingKeys.value = next;
    };

    const clearError = (key: ReactionKey): void => {
        const next = { ...errors.value };
        delete next[key];
        errors.value = next;
    };

    const setError = (key: ReactionKey): void => {
        errors.value = { ...errors.value, [key]: reactionError };
    };

    watch(
        () => toValue(options.reactions),
        (incomingReactions) => {
            state.source = incomingReactions;
            reactions.value = mergeIncomingGroups(
                reactions.value,
                normalizeGroups(incomingReactions),
                pendingKeys.value,
            );
        },
        { deep: true },
    );

    const isPending = (key: ReactionKey): boolean => pendingKeys.value.has(key);

    const getRequest = (key: ReactionKey, active: boolean) => {
        if (options.itemType === 'event') {
            return active
                ? destroyEventReaction({ event: options.itemId, reaction: key })
                : storeEventReaction({ event: options.itemId, reaction: key });
        }

        return active
            ? destroyTodoReaction({ todo: options.itemId, reaction: key })
            : storeTodoReaction({ todo: options.itemId, reaction: key });
    };

    const toggle = async (key: ReactionKey): Promise<boolean> => {
        if (isPending(key)) {
            return false;
        }

        const currentUser = toValue(options.currentUser);
        if (!currentUser) {
            return false;
        }

        const previousGroup = cloneGroup(
            reactions.value.find((group) => group.key === key),
        );
        const active = Boolean(
            previousGroup?.users.some((user) => user.id === currentUser.id),
        );
        const optimisticGroup = active
            ? previousGroup && {
                  key,
                  users: previousGroup.users.filter(
                      (user) => user.id !== currentUser.id,
                  ),
              }
            : {
                  key,
                  users: [
                      ...(previousGroup?.users.map(cloneUser) ?? []),
                      cloneUser(currentUser),
                  ],
              };

        setPending(key, true);
        clearError(key);
        reactions.value = replaceGroup(reactions.value, key, optimisticGroup);

        try {
            const request = getRequest(key, active);
            const response = await fetch(request.url, {
                method: request.method.toUpperCase(),
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
            });

            if (!response.ok) {
                throw new Error('Reaction request failed');
            }

            const payload = await response.json();
            reactions.value = replaceGroup(
                reactions.value,
                key,
                responseGroup(payload, key),
            );

            return true;
        } catch {
            reactions.value = replaceGroup(reactions.value, key, previousGroup);
            setError(key);

            return false;
        } finally {
            setPending(key, false);
        }
    };

    return {
        reactions: readonly(reactions),
        pendingKeys: computed(() => new Set(pendingKeys.value)),
        errors: readonly(errors),
        isPending,
        toggle,
    };
}
