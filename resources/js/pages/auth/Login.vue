<script setup lang="ts">
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import LoginLayout from '@/layouts/app/LoginLayout.vue';
import { store } from '@/routes/login';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: true,
});

const onSubmit = () => {
    form.remember = true;
    form.post(store());
};
</script>

<template>
    <Head title="Přihlášení" />

    <LoginLayout>
        <UPageCard class="w-full sm:max-w-md">
            <h1>Vítej zpět, <span class="text-primary">kapybáro.</span></h1>
            <form :form="form" @submit.prevent="onSubmit">
                <div class="flex flex-col gap-y-4">
                    <UFormField
                        label="Email"
                        name="email"
                        :error="form.errors.email"
                        required
                    >
                        <UInput
                            v-model="form.email"
                            type="email"
                            class="w-full"
                        />
                    </UFormField>
                    <UFormField
                        label="Heslo"
                        name="password"
                        :error="form.errors.password"
                        required
                    >
                        <UInput
                            v-model="form.password"
                            type="password"
                            class="w-full"
                        />
                    </UFormField>

                    <PrimaryButton type="submit" @click="onSubmit">
                        Přihlásit se
                    </PrimaryButton>
                </div>
            </form>
        </UPageCard>
    </LoginLayout>
</template>
