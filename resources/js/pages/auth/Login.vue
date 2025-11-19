<script setup lang="ts">
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import LoginLayout from '@/layouts/app/LoginLayout.vue';
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
        label: 'Heslo',
    },
]);
</script>

<template>
    <Head title="Přihlášení" />

    <LoginLayout>
        <UPageCard class="w-full sm:max-w-md">
            <UAuthForm
                title="Vítej zpět, kapybáro."
                @submit="onSubmit"
                :fields="fields"
                :submit-button="{ label: 'Přihlásit se' }"
            >
                <template #submit>
                    <PrimaryButton type="submit" class="w-full justify-center">
                        Přihlásit se
                    </PrimaryButton>
                </template>
            </UAuthForm>
        </UPageCard>
    </LoginLayout>
</template>
