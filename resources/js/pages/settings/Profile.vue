<script setup lang="ts">
import NotificationSettings from '@/components/settings/NotificationSettings.vue';
import PasswordForm from '@/components/settings/PasswordForm.vue';
import AvatarSection from '@/components/ui/AvatarSection.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();
const items = [
    {
        label: 'Změna hesla',
        icon: 'i-lucide-lock-keyhole',
        slot: 'password',
    },
    {
        label: 'Notifikace',
        icon: 'i-lucide-bell',
        slot: 'notifications',
    },
];
</script>

<template>
    <Head :title="page.props.auth.user.name" />

    <AuthenticatedLayout
        :display-footer="true"
        :display-floating-action-button="false"
    >
        <section>
            <AvatarSection />
        </section>
        <section class="mt-4">
            <UTabs :items="items">
                <template #password>
                    <div class="mt-4">
                        <PasswordForm />
                    </div>
                </template>

                <template #notifications>
                    <section class="mt-4">
                        <NotificationSettings />
                    </section>
                </template>
            </UTabs>
        </section>
    </AuthenticatedLayout>
</template>

<style scoped></style>
