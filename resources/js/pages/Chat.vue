<script setup lang="ts">
import ChatMessageInput from '@/components/chat/ChatMessageInput.vue';
import ChatMessageList from '@/components/chat/ChatMessageList.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import type { Message } from '@/types/Message';
import { Head } from '@inertiajs/vue3';

interface Props {
    messages: Message[];
}

const props = defineProps<Props>();
</script>

<template>
    <Head title="Chat" />
    <AuthenticatedLayout
        :display-footer="false"
        :display-floating-action-button="false"
    >
        <div class="flex h-[calc(100vh-8rem)] flex-col">
            <ChatMessageList :messages="props.messages" />
            <ChatMessageInput />
        </div>
    </AuthenticatedLayout>
</template>
