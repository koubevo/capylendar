<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface WayfinderRoute {
    url: string;
    method: string;
}

interface Props {
    to: WayfinderRoute | string;
    label: string;
    icon?: string;
    isNew?: boolean;
}

const props = defineProps<Props>();
</script>

<template>
    <Link
        :href="typeof props.to === 'string' ? props.to : props.to.url"
        class="block h-full w-full"
    >
        <div
            class="group relative flex h-full flex-row items-center justify-start gap-x-3 rounded-2xl bg-white p-3 shadow-sm ring-1 ring-primary-500/20 transition-all ring-inset hover:-translate-y-0.5 hover:bg-primary-50 hover:shadow-md sm:gap-x-4 sm:p-4 md:flex-col md:justify-center md:gap-y-2 md:p-5 dark:bg-gray-900 dark:ring-primary-400/20 dark:hover:bg-primary-900/20"
        >
            <div
                class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600 transition-colors group-hover:bg-primary-100 sm:size-10 md:size-12 dark:bg-primary-900/50 dark:text-primary-400 dark:group-hover:bg-primary-800"
            >
                <UIcon
                    :name="props.icon || 'i-lucide-chevron-right'"
                    class="size-5 md:size-6"
                />
            </div>

            <h2
                class="m-0 flex-1 pt-1 text-left text-xs font-semibold tracking-wide text-gray-700 sm:text-sm md:pt-0 md:text-center md:text-base dark:text-gray-200"
            >
                {{ props.label }}
            </h2>

            <UBadge
                v-if="props.isNew"
                class="absolute -top-1.5 -right-1 shrink-0 !bg-teal-500 !text-white !ring-teal-500 md:top-3 md:right-3 dark:!bg-teal-400 dark:!ring-teal-400"
                color="primary"
                size="xs"
                variant="solid"
            >
                Novinka
            </UBadge>
        </div>
    </Link>
</template>
