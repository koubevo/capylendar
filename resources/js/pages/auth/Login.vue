<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import LoginLayout from '@/layouts/app/LoginLayout.vue';

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

    <LoginLayout>
        <UPageCard>
            <UAuthForm
                title="Vítej zpět, kapybáro."
                @submit="onSubmit"
                :fields="fields"
            >
            </UAuthForm>
        </UPageCard>
    </LoginLayout>
</template>
