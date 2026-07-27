<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

interface Props {
    size?: 'small' | 'large';
}
type AvatarSize = NonNullable<Props['size']>;


const props = withDefaults(defineProps<Props>(), {
    size: 'large',
});

const sizeClasses: Record<AvatarSize, string> = {
    small: 'w-26 h-26',
    large: 'w-32 h-32',
};

const currentSizeClass = computed(() => sizeClasses[props.size]);
const user = computed(() => page.props.auth.user);
</script>

<template>
    <div v-if="user" class="flex flex-col items-center gap-y-4">
        <img
            :src="'/images/capys/' + user.capybara + '.jpg'"
            :alt="user.name"
            class="rounded-lg object-cover"
            :class="currentSizeClass"
        />

        <div class="text-center">
            <h2 class="mb-0">{{ user.name }}</h2>
            <p>{{ user.email }}</p>
        </div>
    </div>
</template>
