<script setup lang="ts">
import Logo from '@/components/Logo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <UHeader :toggle="false">
        <template #title>
            <logo />
        </template>

        <template #right>
            <Link>
                <UButton color="primary">Přihlásit se</UButton>
            </Link>
        </template>
    </UHeader>
</template>
