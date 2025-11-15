<script setup lang="ts">
import GuestLayout from '@/layouts/app/GuestLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const fields = ref<AuthFormField[]>([
    {
        name: 'email',
        type: 'email',
        label: 'Email',
    },
    {
        name: 'password',
        type: 'password',
        label: 'Password',
    },
]);
</script>

<template>
    <Head title="Přihlášení" />

    <GuestLayout>
        <UPageCard>
            <UAuthForm
                title="Vítej zpět, kapybáro."
                @submit="onSubmit"
                :fields="fields"
            >
            </UAuthForm>
        </UPageCard>
    </GuestLayout>
</template>
