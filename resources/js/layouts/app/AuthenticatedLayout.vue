<script setup lang="ts">
import AuthenticatedHeader from '@/components/authenticated/AuthenticatedHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
</script>

<template>
    <AppLayout>
        <AuthenticatedHeader />

        <UMain>
            <UContainer>
                <slot />
            </UContainer>
        </UMain>
    </AppLayout>
</template>
