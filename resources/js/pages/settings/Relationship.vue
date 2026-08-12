<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import type {
    RelationshipSettings,
    RelationshipSummary,
} from '@/types/Relationship';
import { Head, useForm } from '@inertiajs/vue3';
import { parseDate, type CalendarDate } from '@internationalized/date';
import { computed } from 'vue';

const props = defineProps<{
    relationship: RelationshipSettings | null;
    summary: RelationshipSummary | null;
}>();

const form = useForm({
    started_on: props.relationship?.started_on ?? '',
    name: props.relationship?.name ?? '',
    notifications_enabled: props.relationship?.notifications_enabled ?? true,
    confirm_started_on_change: false,
});

const startedOnDate = computed<CalendarDate | undefined>({
    get: () => (form.started_on ? parseDate(form.started_on) : undefined),
    set: (value) => {
        form.started_on = value?.toString() ?? '';
    },
});

function submit(): void {
    form.confirm_started_on_change =
        !!props.relationship?.started_on &&
        props.relationship.started_on !== form.started_on &&
        window.confirm(
            'Změna počátečního data přepočítá budoucí milníky. Pokračovat?',
        );

    if (
        !!props.relationship?.started_on &&
        props.relationship.started_on !== form.started_on &&
        !form.confirm_started_on_change
    ) {
        return;
    }

    form.put('/relationship', { preserveScroll: true });
}
</script>

<template>
    <Head title="Výročí" />

    <AuthenticatedLayout
        :display-footer="true"
        :display-floating-action-button="false"
    >
        <section class="mx-auto max-w-2xl space-y-6 px-4 py-6">
            <div>
                <h1 class="text-2xl font-semibold">Výročí a milníky</h1>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                    Společné datum pro vás oba a chytré připomínky bez
                    technických eventů v kalendáři.
                </p>
            </div>

            <UCard v-if="summary">
                <div
                    class="relative min-h-28 space-y-3 pr-28 sm:min-h-32 sm:pr-32"
                >
                    <p class="text-3xl font-bold">
                        Spolu {{ summary.days_together }} dní
                    </p>
                    <p class="text-neutral-500 dark:text-neutral-400">
                        {{ summary.human_label }}
                    </p>
                    <div v-if="summary.next_milestone">
                        <p class="font-medium">
                            Nejbližší milník:
                            {{ summary.next_milestone.description }}
                        </p>
                        <p class="text-sm text-neutral-500">
                            {{ summary.next_milestone.date_label }}
                            <span v-if="summary.next_milestone.days_remaining">
                                (za
                                {{ summary.next_milestone.days_remaining }}
                                dní)
                            </span>
                        </p>
                    </div>
                    <img
                        src="/images/capys/relationship-loving-v2.png"
                        alt=""
                        class="absolute top-1/2 right-0 h-28 w-28 -translate-y-1/2 object-contain sm:h-32 sm:w-32"
                        aria-hidden="true"
                    />
                </div>
            </UCard>
            <UCard v-if="summary?.upcoming_milestones.length">
                <template #header>Další milníky</template>
                <ul class="space-y-2 text-sm">
                    <li
                        v-for="milestone in summary.upcoming_milestones"
                        :key="`${milestone.type}-${milestone.date}`"
                        class="flex justify-between gap-4"
                    >
                        <span>{{ milestone.description }}</span>
                        <span class="text-neutral-500">{{
                            milestone.date_label
                        }}</span>
                    </li>
                </ul>
            </UCard>

            <UCard>
                <form class="space-y-5" @submit.prevent="submit">
                    <UFormField
                        label="Datum začátku"
                        :error="form.errors.started_on"
                    >
                        <UInputDate
                            v-model="startedOnDate"
                            locale="cs-CZ"
                            class="w-full"
                        />
                    </UFormField>
                    <UFormField
                        label="Společný název"
                        :error="form.errors.name"
                    >
                        <UInput
                            v-model="form.name"
                            class="w-full"
                            placeholder="Např. My dva"
                        />
                    </UFormField>

                    <div
                        class="flex items-center justify-between gap-6 rounded-lg bg-primary-50/70 p-4 ring-1 ring-primary-500/15 ring-inset dark:bg-primary-950/30 dark:ring-primary-400/15"
                    >
                        <div>
                            <p class="font-medium">
                                Upozornit na miln&iacute;ky
                            </p>
                            <p
                                class="mt-1 text-sm text-neutral-500 dark:text-neutral-400"
                            >
                                V&yacute;ro&#269;&iacute;, kulat&aacute; i
                                zaj&iacute;mav&aacute; &#269;&iacute;sla v
                                jedn&eacute; notifikaci.
                            </p>
                        </div>
                        <USwitch
                            v-model="form.notifications_enabled"
                            class="shrink-0"
                        />
                    </div>

                    <UButton type="submit" :loading="form.processing">
                        Uložit nastavení
                    </UButton>
                </form>
            </UCard>
        </section>
    </AuthenticatedLayout>
</template>
