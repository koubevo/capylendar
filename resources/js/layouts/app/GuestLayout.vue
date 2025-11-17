<script setup lang="ts">
import Container from '@/components/Container.vue';
import GuestHeader from '@/components/guest/GuestHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
</script>

<template>
    <AppLayout>
        <GuestHeader />

        <UMain>
            <Container>
                <slot />
            </Container>
        </UMain>
    </AppLayout>
</template>
