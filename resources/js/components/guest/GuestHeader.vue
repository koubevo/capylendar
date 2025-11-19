<script setup lang="ts">
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import Logo from '@/components/Logo.vue';
import { login } from '@/routes';
</script>

<template>
    <UHeader :toggle="false">
        <template #title>
            <logo />
        </template>

        <template #right>
            <PrimaryButton :to="login()"> Přihlásit se </PrimaryButton>
        </template>
    </UHeader>
</template>
