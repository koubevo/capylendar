<script setup lang="ts">
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import { useForm } from '@inertiajs/vue3';

const toast = useToast();
const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const onSubmit = () => {
    form.put(PasswordController.update(), {
        preserveScroll: true,

        onSuccess: () => {
            form.reset();

            toast.add({
                title: 'Heslo změněno',
                description: 'Heslo bylo úspěšně aktualizováno.',
                color: 'primary',
                icon: 'i-heroicons-check-circle',
            });
        },

        onError: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <form @submit.prevent="onSubmit">
        <div class="flex flex-col gap-y-4">
            <UFormField
                label="Současné heslo"
                name="current_password"
                :error="form.errors.current_password"
                required
            >
                <UInput
                    v-model="form.current_password"
                    type="password"
                    class="w-full"
                />
            </UFormField>
            <UFormField
                label="Nové heslo"
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
            <UFormField
                label="Nové heslo (znovu)"
                name="password_confirmation"
                :error="form.errors.password_confirmation"
                required
            >
                <UInput
                    v-model="form.password_confirmation"
                    type="password"
                    class="w-full"
                />
            </UFormField>
            <PrimaryButton type="submit" :loading="form.processing">
                Změnit heslo
            </PrimaryButton>
        </div>
    </form>
</template>

<style scoped></style>
