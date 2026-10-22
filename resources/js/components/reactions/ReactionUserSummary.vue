<script setup lang="ts">
import type { ReactionOption, ReactionUser } from '@/types/Reaction';
import UAvatar from '@nuxt/ui/components/Avatar.vue';
import { computed } from 'vue';

interface Props {
    user: ReactionUser;
    options: readonly ReactionOption[];
}

const props = defineProps<Props>();

const avatarSource = computed(() => `/images/capys/${props.user.capybara}.jpg`);
const accessibleLabel = computed(
    () =>
        `${props.user.name}: ${props.options
            .map((option) => option.label)
            .join(', ')}`,
);
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full bg-white/70 py-1 pr-2 pl-1 shadow-sm ring-1 ring-black/5 dark:bg-white/10 dark:ring-white/10"
        role="img"
        :aria-label="accessibleLabel"
        :title="accessibleLabel"
        data-reaction-user
    >
        <UAvatar
            :src="avatarSource"
            :alt="props.user.name"
            size="2xs"
            class="ring-1 ring-black/10 dark:ring-white/15"
        />
        <span class="flex items-center gap-0.5" aria-hidden="true">
            <span
                v-for="option in props.options"
                :key="option.key"
                class="text-base leading-none"
            >
                {{ option.emoji }}
            </span>
        </span>
    </span>
</template>
