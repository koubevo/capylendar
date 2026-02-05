<script setup lang="ts">
import MessageController from '@/actions/App/Http/Controllers/MessageController';
import { Form } from '@inertiajs/vue3';
</script>

<template>
    <Form
        :action="MessageController.store().url"
        method="post"
        class="shrink-0 border-t border-gray-200 pt-4 dark:border-gray-700"
        reset-on-success
        #default="{ processing, errors }"
    >
        <div class="flex items-center gap-2">
            <UInput
                name="content"
                placeholder="Napiš zprávu..."
                class="flex-1"
            />
            <UButton
                type="submit"
                icon="i-lucide-send"
                :loading="processing"
                aria-label="Odeslat zprávu"
                square
            />
        </div>
        <p v-if="errors.content" class="mt-1 text-sm text-red-500">
            {{ errors.content }}
        </p>
    </Form>
</template>
