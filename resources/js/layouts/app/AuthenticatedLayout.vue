<script setup lang="ts">
import EventController from '@/actions/App/Http/Controllers/EventController';
import AuthenticatedFooter from '@/components/authenticated/AuthenticatedFooter.vue';
import AuthenticatedHeader from '@/components/authenticated/AuthenticatedHeader.vue';
import FloatingActionButton from '@/components/buttons/FloatingActionButton.vue';
import Container from '@/components/Container.vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface Props {
    displayFooter: boolean;
    displayFloatingActionButton: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    displayFooter: false,
    displayFloatingActionButton: true,
});
</script>

<template>
    <AppLayout>
        <AuthenticatedHeader />

        <UMain>
            <Container>
                <slot />
            </Container>
        </UMain>

        <FloatingActionButton
            v-if="props.displayFloatingActionButton"
            aria-label="Přidat event"
            :to="EventController.create()"
        />

        <AuthenticatedFooter v-if="props.displayFooter" />
        <UNotifications />
    </AppLayout>
</template>
