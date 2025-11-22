<script setup lang="ts">
import DashboardController from '@/actions/App/Http/Controllers/DashboardController';
import EventController from '@/actions/App/Http/Controllers/EventController';
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import Logo from '@/components/Logo.vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <UHeader :toggle="false" :to="DashboardController()">
        <template #title>
            <logo />
        </template>

        <template #right>
            <PrimaryButton
                class="hidden md:flex"
                icon="i-lucide-plus"
                :to="EventController.create()"
            >
                Přidat event
            </PrimaryButton>
            <img
                :src="'/images/capys/' + page.props.auth.user.capybara + '.jpg'"
                :alt="page.props.auth.user.name"
                class="ms-2 h-8 w-8 rounded-md"
            />
        </template>
    </UHeader>
</template>
