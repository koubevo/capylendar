<script setup lang="ts">
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import type { Document } from '@/types/Document';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    document: Document;
}>();
</script>

<template>
    <Link :href="DocumentController.show.url(props.document)" class="block">
        <UCard
            class="transition hover:-translate-y-0.5 hover:shadow-md dark:bg-neutral-900"
        >
            <div class="flex flex-col gap-y-3">
                <div class="flex items-start justify-between gap-3">
                    <h3 class="text-lg font-semibold">
                        {{ props.document.title }}
                    </h3>
                    <UIcon
                        name="i-lucide-file-text"
                        class="mt-1 size-5 shrink-0 text-primary"
                    />
                </div>

                <div
                    class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-neutral-500 dark:text-neutral-400"
                >
                    <span>{{ props.document.author.name }}</span>
                    <span>{{ props.document.updated_at_human }}</span>
                </div>
            </div>
        </UCard>
    </Link>
</template>
