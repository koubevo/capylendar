<script setup lang="ts">
import DashboardController from '@/actions/App/Http/Controllers/DashboardController';
import EventController from '@/actions/App/Http/Controllers/EventController';
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import Logo from '@/components/Logo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <UHeader :toggle="false" :to="DashboardController()">
        <template #title>
            <logo />
        </template>

        <template #right>
            <Link>
                <PrimaryButton
                    icon="i-lucide-plus"
                    :to="EventController.create()"
                >
                    Přidat event
                </PrimaryButton>
            </Link>
        </template>
    </UHeader>
</template>
