<script setup lang="ts">
import type { Message } from '@/types/Message';

interface Props {
    message: Message;
    isCurrentUser: boolean;
}

const props = defineProps<Props>();
</script>

<template>
    <div
        class="flex gap-3"
        :class="{
            'flex-row-reverse': props.isCurrentUser,
        }"
    >
        <img
            :src="props.message.user.capybara.avatar.src"
            :alt="props.message.user.name"
            class="h-10 w-10 shrink-0 self-end rounded-md"
        />
        <div
            class="max-w-[75%] rounded-lg p-3"
            :class="props.message.user.capybara.classes"
        >
            <div class="mb-1 flex flex-wrap items-center gap-x-2">
                <span class="text-sm font-semibold">{{
                    props.message.user.name
                }}</span>
                <span class="text-xs text-gray-500">{{
                    props.message.created_at_human
                }}</span>
            </div>
            <p class="text-sm whitespace-pre-wrap">
                {{ props.message.content }}
            </p>
        </div>
    </div>
</template>
