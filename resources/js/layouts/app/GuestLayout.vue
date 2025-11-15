<script setup lang="ts">
import GuestHeader from '@/components/guest/GuestHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
</script>

<template>
    <AppLayout>
        <GuestHeader />

        <UMain>
            <UContainer>
                <slot />
            </UContainer>
        </UMain>
    </AppLayout>
</template>
