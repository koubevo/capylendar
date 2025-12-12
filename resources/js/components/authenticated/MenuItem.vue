<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Props {
    to: string;
    label: string;
    isNew?: boolean;
}

const props = defineProps<Props>();
</script>

<template>
    <Link :href="props.to">
        <h2 class="m-0 flex items-center gap-x-1">
            <UIcon name="i-lucide-chevrons-right" class="mt-0.5" />
            <UBadge v-if="props.isNew" class="me-1 mt-1" color="success">
                Novinka
            </UBadge>
            {{ props.label }}
        </h2>
    </Link>
</template>
