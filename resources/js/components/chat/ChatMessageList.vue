<script setup lang="ts">
import ChatMessage from '@/components/chat/ChatMessage.vue';
import type { Message } from '@/types/Message';
import { usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

interface Props {
    messages: Message[];
}

const props = defineProps<Props>();

const page = usePage();
const currentUserId = computed(() => page.props.auth.user.id);

const messagesContainer = ref<HTMLElement | null>(null);

const scrollToBottom = () => {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop =
            messagesContainer.value.scrollHeight;
    }
};

onMounted(() => {
    scrollToBottom();
});

watch(
    () => props.messages.length,
    () => {
        nextTick(() => scrollToBottom());
    },
);
</script>

<template>
    <div ref="messagesContainer" class="flex-1 space-y-4 overflow-y-auto pb-4">
        <template v-if="props.messages.length">
            <ChatMessage
                v-for="message in props.messages"
                :key="message.id"
                :message="message"
                :is-current-user="message.user.id === currentUserId"
            />
        </template>
        <template v-else>
            <div
                class="flex h-full flex-col items-center justify-center text-gray-500"
            >
                <UIcon name="i-lucide-message-circle" class="mb-2 size-12" />
                <p>Žádné zprávy. Začni konverzaci!</p>
            </div>
        </template>
    </div>
</template>
