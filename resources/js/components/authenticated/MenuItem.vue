<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Props {
    to: any;
    label: string;
    icon?: string;
    isNew?: boolean;
}

const props = defineProps<Props>();
</script>

<template>
    <Link :href="props.to.url || props.to" class="block w-full h-full mt-1">
        <div class="hover:bg-primary-50 dark:hover:bg-primary-900/20 ring-primary-500/20 dark:ring-primary-400/20 group relative flex flex-row md:flex-col items-center justify-start md:justify-center gap-x-3 sm:gap-x-4 md:gap-y-2 rounded-2xl bg-white p-3 sm:p-4 md:p-5 shadow-sm ring-1 ring-inset transition-all hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-900 h-full">
            
            <div class="bg-primary-50 text-primary-600 dark:bg-primary-900/50 dark:text-primary-400 flex size-9 sm:size-10 md:size-12 shrink-0 items-center justify-center rounded-xl transition-colors group-hover:bg-primary-100 dark:group-hover:bg-primary-800">
                <UIcon :name="props.icon || 'i-lucide-chevron-right'" class="size-5 md:size-6" />
            </div>
            
            <h2 class="text-gray-700 dark:text-gray-200 m-0 text-left md:text-center text-xs sm:text-sm md:text-base font-semibold tracking-wide flex-1 pt-1 md:pt-0">
                {{ props.label }}
            </h2>

            <UBadge v-if="props.isNew" class="absolute -top-1.5 -right-1 md:right-3 md:top-3 shrink-0 !bg-teal-500 dark:!bg-teal-400 !text-white !ring-teal-500 dark:!ring-teal-400" color="primary" size="xs" variant="solid">
                Novinka
            </UBadge>
        </div>
    </Link>
</template>
