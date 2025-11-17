<script setup lang="ts">
import AuthenticatedHeader from '@/components/authenticated/AuthenticatedHeader.vue';
import Container from '@/components/Container.vue';
import AppLayout from '@/layouts/AppLayout.vue';
</script>

<template>
    <AppLayout>
        <AuthenticatedHeader />

        <UMain>
            <Container>
                <slot />
            </Container>
        </UMain>
    </AppLayout>
</template>
