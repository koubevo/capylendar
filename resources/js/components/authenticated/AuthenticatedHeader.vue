<script setup lang="ts">
import DashboardController from '@/actions/App/Http/Controllers/DashboardController';
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import EventController from '@/actions/App/Http/Controllers/EventController';
import MessageController from '@/actions/App/Http/Controllers/MessageController';
import { index as watchDevicesIndex } from '@/actions/App/Http/Controllers/Settings/WatchDeviceController';
import TagController from '@/actions/App/Http/Controllers/TagController';
import TodoController from '@/actions/App/Http/Controllers/TodoController';
import MenuItem from '@/components/authenticated/MenuItem.vue';
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import Logo from '@/components/Logo.vue';
import AvatarSection from '@/components/ui/AvatarSection.vue';
import { profile } from '@/routes';
import { index as relationshipSettingsIndex } from '@/routes/relationship-settings';
import type { AppPageProps } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage<AppPageProps>();
const relationshipDescription = computed(() => {
    const summary = page.props.relationshipSummary;

    return summary
        ? `${summary.days_together.toLocaleString('cs-CZ')} dn\u00ed \u00b7 ${summary.human_label}`
        : undefined;
});

const links = computed(() => [
    {
        label: 'Dashboard',
        to: DashboardController(),
        icon: 'i-lucide-layout-dashboard',
    },
    {
        label: 'Todos',
        to: TodoController.index(),
        icon: 'i-lucide-list-todo',
    },
    {
        label: 'Dokumenty',
        to: DocumentController.index(),
        icon: 'i-lucide-file-text',
    },
    {
        label: 'Chat',
        to: MessageController.index(),
        icon: 'i-lucide-message-circle',
    },
    {
        label: 'Vztah',
        to: relationshipSettingsIndex(),
        icon: 'i-lucide-heart',
        isNew: true,
        description: relationshipDescription.value,
    },
    {
        label: 'Můj Profil',
        to: profile(),
        icon: 'i-lucide-user',
    },
    {
        label: 'Spárovaná zařízení',
        to: watchDevicesIndex(),
        icon: 'i-lucide-monitor-smartphone',
    },
    {
        label: 'Správa štítků',
        to: TagController.index(),
        icon: 'i-lucide-tags',
    },
    {
        label: 'Historie',
        to: DashboardController.historyIndex(),
        icon: 'i-lucide-history',
    },
    {
        label: 'Smazané eventy',
        to: EventController.deletedIndex(),
        icon: 'i-lucide-calendar-off',
    },
    {
        label: 'Smazaná todos',
        to: TodoController.deletedIndex(),
        icon: 'i-lucide-list-x',
    },
]);

const addMenuItems = [
    [
        {
            label: 'Přidat event',
            icon: 'i-lucide-calendar-plus',
            onSelect: () => router.visit(EventController.create.url()),
        },
        {
            label: 'Přidat todo',
            icon: 'i-lucide-list-todo',
            onSelect: () => router.visit(TodoController.create.url()),
        },
        {
            label: 'Přidat dokument',
            icon: 'i-lucide-file-plus',
            onSelect: () => router.visit(DocumentController.create.url()),
        },
    ],
];
</script>

<template>
    <UHeader
        :to="DashboardController.url()"
        :ui="{
            toggle: 'visible lg:flex',
            content: 'lg:block max-h-dvh overflow-y-auto overscroll-contain',
            body: 'min-h-0 overflow-y-auto',
            header: 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 w-full',
        }"
    >
        <template #title>
            <logo />
        </template>

        <template #body>
            <section
                class="flex w-full flex-col items-center gap-y-8 px-4 py-6"
            >
                <Link
                    :href="profile.url()"
                    class="transition-transform hover:scale-105"
                >
                    <AvatarSection size="large" />
                </Link>

                <div
                    class="grid w-full max-w-4xl grid-cols-2 gap-2 sm:gap-3 md:grid-cols-3 md:gap-4 lg:grid-cols-4"
                >
                    <MenuItem
                        v-for="link in links"
                        :key="link.to.url"
                        :to="link.to"
                        :label="link.label"
                        :icon="link.icon"
                        :isNew="link.isNew"
                        :description="link.description"
                    />
                </div>
            </section>
        </template>

        <template #right>
            <UDropdownMenu :items="addMenuItems">
                <PrimaryButton class="hidden md:flex" icon="i-lucide-plus">
                    Přidat
                </PrimaryButton>
            </UDropdownMenu>
        </template>
    </UHeader>
</template>
