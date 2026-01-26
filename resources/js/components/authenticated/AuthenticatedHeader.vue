<script setup lang="ts">
import DashboardController from '@/actions/App/Http/Controllers/DashboardController';
import EventController from '@/actions/App/Http/Controllers/EventController';
import TagController from '@/actions/App/Http/Controllers/TagController';
import MenuItem from '@/components/authenticated/MenuItem.vue';
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import Logo from '@/components/Logo.vue';
import AvatarSection from '@/components/ui/AvatarSection.vue';
import { profile } from '@/routes';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const links = computed(() => [
    {
        label: 'Dashboard',
        to: DashboardController(),
    },
    {
        label: 'Můj Profil',
        to: profile(),
    },
    {
        label: 'Správa štítků',
        to: TagController.index(),
    },
    {
        label: 'Obnovit eventy',
        to: EventController.deletedIndex(),
    },
]);
</script>

<template>
    <UHeader
        :to="DashboardController()"
        :ui="{
            toggle: 'visible lg:flex',
            content: 'lg:block',
        }"
    >
        <template #title>
            <logo />
        </template>

        <template #body>
            <section class="md-w-1/5">
                <Link :href="profile()">
                    <AvatarSection size="small" />
                </Link>
                <USeparator class="my-8" />
                <div class="flex flex-col gap-y-4">
                    <MenuItem
                        v-for="link in links"
                        :key="link.to"
                        :to="link.to"
                        :label="link.label"
                        :isNew="link.isNew"
                    />
                </div>
            </section>
        </template>

        <template #right>
            <PrimaryButton
                class="hidden md:flex"
                icon="i-lucide-plus"
                :to="EventController.create()"
            >
                Přidat event
            </PrimaryButton>
        </template>
    </UHeader>
</template>
