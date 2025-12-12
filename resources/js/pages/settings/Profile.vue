<script setup lang="ts">
import Construction from '@/components/Construction.vue';
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
        label: 'Připravujeme',
        icon: 'i-lucide-construction',
        slot: 'coming-soon',
    },
];
</script>

<template>
    <Head :title="page.props.auth.user.name" />

    <AuthenticatedLayout :display-footer="true">
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

                <template #coming-soon>
                    <section class="mt-4">
                        <Construction />
                    </section>
                </template>
            </UTabs>
        </section>
    </AuthenticatedLayout>
</template>

<style scoped></style>
