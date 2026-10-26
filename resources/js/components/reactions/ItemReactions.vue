<script setup lang="ts">
import ReactionUserSummary from '@/components/reactions/ReactionUserSummary.vue';
import {
    type ReactionItemType,
    useItemReactions,
} from '@/composables/useItemReactions';
import type { AppPageProps, User } from '@/types/index';
import {
    type ReactionGroup,
    type ReactionOption,
    type ReactionUser,
} from '@/types/Reaction';
import { usePage } from '@inertiajs/vue3';
import UButton from '@nuxt/ui/components/Button.vue';
import UPopover from '@nuxt/ui/components/Popover.vue';
import { computed, ref, toRef } from 'vue';

interface Props {
    itemType: ReactionItemType;
    itemId: number;
    reactions?: ReactionGroup[];
    interactive?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    interactive: true,
});

const page = usePage<AppPageProps>();

const currentUser = computed<ReactionUser | null>(() => {
    const user = page.props.auth?.user;

    return user ? reactionUserFromAuth(user) : null;
});

const options = computed<ReactionOption[]>(() => {
    return page.props.reactionOptions ?? [];
});

const { reactions, errors, isPending, toggle } = useItemReactions({
    itemType: props.itemType,
    itemId: props.itemId,
    reactions: toRef(props, 'reactions'),
    currentUser,
});

const pickerOpen = ref(false);

const canInteract = computed(
    () => props.interactive && currentUser.value !== null,
);

const groupsByKey = computed(
    () => new Map(reactions.value.map((group) => [group.key, group])),
);

const catalogEntries = computed(() =>
    options.value.map((option) => ({
        option,
        group: groupsByKey.value.get(option.key),
    })),
);

const userEntries = computed(() => {
    const entries = new Map<
        number,
        { user: ReactionUser; options: ReactionOption[] }
    >();

    for (const option of options.value) {
        for (const user of groupsByKey.value.get(option.key)?.users ?? []) {
            const existingEntry = entries.get(user.id);

            entries.set(user.id, {
                user,
                options: [...(existingEntry?.options ?? []), option],
            });
        }
    }

    return [...entries.values()].sort(
        (first, second) => first.user.id - second.user.id,
    );
});

const errorEntries = computed(() =>
    options.value.filter((option) => errors.value[option.key]),
);

const hasContent = computed(
    () => canInteract.value || userEntries.value.length > 0,
);

const isCompact = computed(
    () => userEntries.value.length === 0 && errorEntries.value.length === 0,
);

const isActive = (group: ReactionGroup | undefined): boolean =>
    Boolean(group?.users.some((user) => user.id === currentUser.value?.id));

const toggleReaction = (key: ReactionOption['key']): void => {
    pickerOpen.value = false;
    void toggle(key);
};

function reactionUserFromAuth(user: User): ReactionUser {
    return {
        id: user.id,
        name: user.name,
        capybara: user.capybara,
    };
}
</script>

<template>
    <div
        v-if="hasContent"
        :class="
            isCompact
                ? 'absolute right-1.5 bottom-1.5'
                : 'border-t border-black/5 px-4 py-2 dark:border-white/10'
        "
        data-reaction-bar
        @click.stop
    >
        <div class="flex flex-wrap items-center gap-2">
            <ReactionUserSummary
                v-for="entry in userEntries"
                :key="entry.user.id"
                :user="entry.user"
                :options="entry.options"
            />

            <UPopover v-if="canInteract" v-model:open="pickerOpen">
                <UButton
                    type="button"
                    size="xs"
                    variant="ghost"
                    color="neutral"
                    :icon="isCompact ? 'i-lucide-smile-plus' : 'i-lucide-plus'"
                    aria-label="Přidat reakci"
                    title="Přidat reakci"
                    :class="
                        isCompact
                            ? 'rounded-full bg-white/80 text-gray-500 shadow-sm ring-1 ring-black/5 dark:bg-gray-900/70 dark:text-gray-300 dark:ring-white/10'
                            : ''
                    "
                    @click.stop
                />

                <template #content>
                    <div class="flex items-center gap-1 p-1">
                        <UButton
                            v-for="entry in catalogEntries"
                            :key="entry.option.key"
                            type="button"
                            size="sm"
                            :variant="isActive(entry.group) ? 'soft' : 'ghost'"
                            color="neutral"
                            :disabled="isPending(entry.option.key)"
                            :aria-pressed="isActive(entry.group)"
                            :aria-label="entry.option.label"
                            :title="entry.option.label"
                            class="h-9 w-9 justify-center px-0 text-lg"
                            @click.stop="toggleReaction(entry.option.key)"
                        >
                            <span aria-hidden="true">{{
                                entry.option.emoji
                            }}</span>
                        </UButton>
                    </div>
                </template>
            </UPopover>
        </div>

        <div v-if="errorEntries.length" class="mt-1 space-y-0.5">
            <p
                v-for="option in errorEntries"
                :key="option.key"
                class="text-xs text-rose-600 dark:text-rose-400"
                role="status"
            >
                {{ option.emoji }} {{ option.label }}: {{ errors[option.key] }}
            </p>
        </div>
    </div>
</template>
