<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const items = [
    {
        label: 'Nadcházející',
        icon: 'i-lucide-rocket',
        slot: 'coming',
    },
    {
        label: 'Historické',
        icon: 'i-lucide-history',
        slot: 'history',
    },
];
</script>

<template>
    <Head title="Domů" />

    <AuthenticatedLayout>
        <UTabs :items="items">
            <template #coming>
                <section class="mt-4">
                    <h1>Nadcházející</h1>
                </section>
            </template>

            <template #history>
                <section class="mt-4">
                    <h1>Historické</h1>
                </section>
            </template>
        </UTabs>
    </AuthenticatedLayout>
</template>
