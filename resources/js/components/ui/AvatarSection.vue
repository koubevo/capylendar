<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

interface Props {
    size?: 'small' | 'large';
}

const props = withDefaults(defineProps<Props>(), {
    size: 'large',
});

const sizeClasses: Record<Props['size'], string> = {
    small: 'w-26 h-26',
    large: 'w-32 h-32',
};

const currentSizeClass = computed(() => sizeClasses[props.size]);
</script>

<template>
    <div class="flex flex-col items-center gap-y-4">
        <img
            :src="'/images/capys/' + page.props.auth.user.capybara + '.jpg'"
            :alt="page.props.auth.user.name"
            class="rounded-lg object-cover"
            :class="currentSizeClass"
        />

        <div class="text-center">
            <h2 class="mb-0">{{ page.props.auth.user.name }}</h2>
            <p>{{ page.props.auth.user.email }}</p>
        </div>
    </div>
</template>
