<script setup lang="ts">
import DashboardList from '@/components/dashboard/DashboardList.vue';
import EventsList from '@/components/events/EventsList.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import GuestLayout from '@/layouts/app/GuestLayout.vue';
import { login } from '@/routes';
import type { Event } from '@/types/Event';
import type { Todo } from '@/types/Todo';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const githubUrl = 'https://github.com/koubevo/capylendar';
const linkedinUrl = 'https://linkedin.com/in/koubevo';
const emailUrl = 'mailto:vojtech.koubek@seznam.cz';

const page = usePage();

const features = [
    {
        name: 'Společné i soukromé',
        description:
            'Event nebo todo může patřit oběma, anebo zůstat jen vaším soukromým plánem.',
        icon: 'i-lucide-users-round',
        accent: 'bg-[#ffd9e7]',
        span: 'md:col-span-2',
    },
    {
        name: 'Den na zápěstí',
        description:
            'Wear OS přehled ukáže nejbližší události a umožní odškrtnout úkol bez telefonu.',
        icon: 'i-lucide-watch',
        accent: 'bg-[#d9ecff]',
        span: '',
    },
    {
        name: 'Push notifikace',
        description: 'Ranní a večerní souhrny i chatové zprávy.',
        icon: 'i-lucide-bell-ring',
        accent: 'bg-[#fff0b8]',
        span: '',
    },
    {
        name: 'Další měsíc až ve chvíli, kdy ho potřebujete',
        description:
            'Dashboard načítá plány po kalendářních měsících. Historie používá plynulý Inertia infinite scroll.',
        icon: 'i-lucide-calendar-range',
        accent: 'bg-[#dff5df]',
        span: 'md:col-span-2',
    },
    {
        name: 'Dokumenty a tagy pro oba',
        description:
            'Společné poznámky, důležité odkazy a vlastní barevné štítky na jednom místě.',
        icon: 'i-lucide-notebook-tabs',
        accent: 'bg-[#e9ddff]',
        span: '',
    },
    {
        name: 'Obrázky, mapy a chat',
        description:
            'Fotky u eventů, chytré náhledy mapových odkazů a jednoduchá konverzace ve dvou.',
        icon: 'i-lucide-images',
        accent: 'bg-[#ffdccc]',
        span: '',
    },
    {
        name: 'PWA bez dalšího app storu',
        description:
            'Na mobilu se chová jako aplikace, na hodinky se debug APK jednoduše nahraje z notebooku.',
        icon: 'i-lucide-smartphone',
        accent: 'bg-[#d8f3ef]',
        span: '',
    },
];

const stack = [
    { name: 'Laravel 13', icon: 'i-logos-laravel' },
    { name: 'Inertia 3', icon: 'simple-icons:inertia' },
    { name: 'Vue 3', icon: 'i-logos-vue' },
    { name: 'TypeScript', icon: 'i-logos-typescript-icon' },
    { name: 'Tailwind 4', icon: 'i-logos-tailwindcss-icon' },
    { name: 'PostgreSQL 18', icon: 'i-logos-postgresql' },
    { name: 'Pest 4', icon: 'i-lucide-test-tube-diagonal' },
    { name: 'Wear OS', icon: 'i-lucide-watch' },
];

const previewTabs = [
    {
        label: 'Nadcházející',
        icon: 'i-lucide-rocket',
        slot: 'upcoming',
    },
    {
        label: 'Historické',
        icon: 'i-lucide-history',
        slot: 'history',
    },
];

const today = new Date();
const tomorrow = new Date();
tomorrow.setDate(today.getDate() + 1);

const upcomingEvents: Event[] = [
    {
        id: 1,
        title: 'Výročí ❤️',
        date: {
            key: today.toISOString().split('T')[0],
            label: 'dnes',
            start_time: '',
            end_time: '',
            is_all_day: true,
        },
        capybara: {
            value: 'pink',
            label: 'Stacy',
            classes: 'bg-pink-100 md:bg-pink-50 hover:bg-pink-100',
            link_classes: 'text-pink-500 hover:text-pink-600',
            avatar: { src: '/images/capys/pink.jpg', alt: 'Pink' },
        },
        is_private: false,
        has_hearts: true,
        created_at_human: 'dnes',
        author: {
            id: 2,
            name: 'Stacy',
            email: 'stacy@example.com',
            capybara: 'pink',
            notifications_enabled: true,
        },
        has_map_meta: false,
        tags: [
            {
                id: 1,
                label: 'My dva',
                color: '#f6339a',
            },
        ],
    } as Event,
    {
        id: 2,
        title: 'Oční',
        date: {
            key: tomorrow.toISOString().split('T')[0],
            label: 'zítra',
            start_time: '12:00',
            end_time: '12:30',
            is_all_day: false,
        },
        capybara: {
            value: 'blue',
            label: 'John',
            classes: 'bg-blue-100 md:bg-blue-50 hover:bg-blue-100',
            link_classes: 'text-blue-500 hover:text-blue-600',
            avatar: { src: '/images/capys/blue.jpg', alt: 'Blue' },
        },
        is_private: true,
        has_hearts: false,
        created_at_human: 'dnes',
        author: {
            id: 3,
            name: 'John',
            email: 'john@example.com',
            capybara: 'blue',
            notifications_enabled: true,
        },
        has_map_meta: false,
        tags: [
            {
                id: 2,
                label: 'Doktor',
                color: '#00918a',
            },
        ],
    } as Event,
    {
        id: 3,
        title: 'Večeře v osm',
        date: {
            key: tomorrow.toISOString().split('T')[0],
            label: 'zítra',
            start_time: '20:00',
            end_time: '',
            is_all_day: false,
        },
        capybara: {
            value: 'yellow',
            label: 'Oba',
            classes: 'bg-yellow-100 md:bg-yellow-50 hover:bg-yellow-100',
            link_classes: 'text-yellow-500 hover:text-yellow-600',
            avatar: { src: '/images/capys/yellow.jpg', alt: 'Yellow' },
        },
        is_private: false,
        has_hearts: false,
        created_at_human: 'dnes',
        author: {
            id: 3,
            name: 'John',
            email: 'john@example.com',
            capybara: 'yellow',
            notifications_enabled: true,
        },
        has_map_meta: false,
    } as Event,
];

const upcomingTodos = ref<Todo[]>([
    {
        id: 1,
        title: 'Koupit letenky',
        is_finished: false,
        priority: {
            value: 'high',
            label: 'Vysoká',
            icon: 'i-lucide-arrow-up',
            border_class: 'border-red-500',
            icon_color: 'text-red-500',
            checkbox_color: 'text-red-500',
        },
        deadline: {
            key: today.toISOString().split('T')[0],
            label: 'dnes',
        },
        capybara: {
            value: 'blue',
            label: 'John',
            classes: 'bg-blue-100 md:bg-blue-50 hover:bg-blue-100',
            link_classes: 'text-blue-500 hover:text-blue-600',
            avatar: { src: '/images/capys/blue.jpg', alt: 'Blue' },
        },
        is_private: false,
        has_hearts: false,
        has_map_meta: false,
        created_at_human: 'dnes',
        author: {
            id: 3,
            name: 'John',
            email: 'john@example.com',
            capybara: 'blue',
            notifications_enabled: true,
        },
    } as Todo,
]);

const historyEvents: Event[] = [];

const handleToggled = (id: number) => {
    upcomingTodos.value = upcomingTodos.value.map((todo) =>
        todo.id === id ? { ...todo, is_finished: !todo.is_finished } : todo,
    );
};
</script>

<template>
    <Head title="Sdílený kalendář pro dva" />

    <GuestLayout>
        <main
            class="overflow-hidden bg-[#f4efe6] text-[#18243d] selection:bg-[#18243d] selection:text-white"
        >
            <section class="paper-grid relative border-b border-[#18243d]/15">
                <div
                    class="mx-auto grid max-w-7xl gap-14 px-6 py-16 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:px-10 lg:py-24"
                >
                    <div class="relative z-10">
                        <div
                            class="mb-8 inline-flex rotate-[-1deg] items-center gap-2 border border-[#18243d] bg-[#fff8ec] px-3 py-2 font-mono text-xs font-bold tracking-[0.14em] uppercase shadow-[3px_3px_0_#18243d]"
                        >
                            <span
                                class="size-2 rounded-full bg-[#ee4f87]"
                            ></span>
                            v{{ page.props.version }} · používáno každý den
                        </div>

                        <h1
                            class="max-w-4xl text-[clamp(3.6rem,8vw,7.7rem)] leading-[0.86] font-black tracking-[-0.075em]"
                        >
                            Méně
                            <span
                                class="relative inline-block text-[#ee4f87] after:absolute after:right-0 after:-bottom-2 after:left-0 after:h-2 after:rotate-[-1deg] after:bg-[#ffd04a] after:content-['']"
                            >
                                domlouvání.
                            </span>
                            <br />
                            Více společných plánů.
                        </h1>

                        <p
                            class="mt-9 max-w-2xl text-lg leading-relaxed text-[#4a5368] md:text-xl"
                        >
                            Capylendar je soukromý digitální stůl pro dva.
                            Kalendář, úkoly, dokumenty, chat a hodinky drží
                            každodenní chaos pohromadě — bez další skupiny,
                            tabulky nebo předplatného.
                        </p>

                        <div class="mt-10 flex flex-wrap gap-4">
                            <Link
                                :href="login()"
                                class="group inline-flex items-center gap-3 rounded-full bg-[#18243d] px-7 py-4 font-bold text-white shadow-[0_10px_30px_rgba(24,36,61,0.2)] transition-transform hover:-translate-y-1"
                            >
                                Otevřít Capylendar
                                <UIcon
                                    name="i-lucide-arrow-up-right"
                                    class="size-5 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                                />
                            </Link>
                            <a
                                :href="githubUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-3 rounded-full border-2 border-[#18243d] bg-[#fff8ec] px-7 py-4 font-bold shadow-[4px_4px_0_#18243d] transition-all hover:translate-x-1 hover:translate-y-1 hover:shadow-none"
                            >
                                <UIcon name="i-lucide-github" class="size-5" />
                                Zdrojový kód
                            </a>
                        </div>
                    </div>

                    <div class="relative mx-auto w-full max-w-xl lg:mr-0">
                        <div
                            class="absolute -top-12 -right-8 size-48 rounded-full bg-[#ffd04a]/70 blur-3xl"
                        ></div>
                        <div
                            class="absolute -bottom-14 -left-10 size-56 rounded-full bg-[#ee4f87]/25 blur-3xl"
                        ></div>

                        <div
                            class="relative rotate-[1.5deg] rounded-[2rem] border-2 border-[#18243d] bg-[#fffaf2] p-5 shadow-[14px_16px_0_#18243d]"
                        >
                            <div
                                class="mb-5 flex items-center justify-between border-b border-dashed border-[#18243d]/25 pb-4"
                            >
                                <div>
                                    <p
                                        class="font-mono text-[10px] font-bold tracking-[0.2em] text-[#707789] uppercase"
                                    >
                                        úterý · doma
                                    </p>
                                    <p class="mt-1 text-2xl font-black">
                                        Co nás čeká
                                    </p>
                                </div>
                                <div class="flex -space-x-3">
                                    <img
                                        src="/images/capys/pink.jpg"
                                        alt="Růžová kapybara"
                                        class="size-12 rounded-full border-4 border-[#fffaf2] object-cover"
                                    />
                                    <img
                                        src="/images/capys/blue.jpg"
                                        alt="Modrá kapybara"
                                        class="size-12 rounded-full border-4 border-[#fffaf2] object-cover"
                                    />
                                </div>
                            </div>

                            <div class="grid gap-3">
                                <div
                                    class="flex items-center gap-4 rounded-2xl bg-[#ffd9e7] p-4"
                                >
                                    <span
                                        class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-white/70 text-xl font-black"
                                        >18</span
                                    >
                                    <div class="min-w-0 flex-1">
                                        <p class="font-black">Výročí ❤️</p>
                                        <p class="text-sm text-[#62697a]">
                                            celý den · oba
                                        </p>
                                    </div>
                                    <UIcon
                                        name="i-lucide-heart"
                                        class="size-5 text-[#ee4f87]"
                                    />
                                </div>
                                <div
                                    class="flex items-center gap-4 rounded-2xl bg-[#d9ecff] p-4"
                                >
                                    <span
                                        class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-white/70 font-mono text-sm font-black"
                                        >12:00</span
                                    >
                                    <div class="min-w-0 flex-1">
                                        <p class="font-black">Oční</p>
                                        <p class="text-sm text-[#62697a]">
                                            soukromý event
                                        </p>
                                    </div>
                                    <UIcon
                                        name="i-lucide-lock-keyhole"
                                        class="size-5"
                                    />
                                </div>
                                <div
                                    class="flex items-center gap-4 rounded-2xl border-2 border-dashed border-[#18243d]/25 p-4"
                                >
                                    <span
                                        class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-[#fff0b8]"
                                    >
                                        <UIcon
                                            name="i-lucide-check"
                                            class="size-6"
                                        />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-black">Koupit letenky</p>
                                        <p class="text-sm text-[#62697a]">
                                            vysoká priorita
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="absolute -right-4 -bottom-10 rotate-[-4deg] rounded-2xl border-2 border-[#18243d] bg-[#ffd04a] px-5 py-4 shadow-[6px_7px_0_#18243d] sm:right-2"
                        >
                            <div class="flex items-center gap-3">
                                <UIcon name="i-lucide-watch" class="size-7" />
                                <div>
                                    <p class="font-mono text-[10px] uppercase">
                                        synced
                                    </p>
                                    <p class="font-black">Wear OS</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-[#fffaf2] py-20 md:py-28">
                <div class="mx-auto max-w-7xl px-6 lg:px-10">
                    <div
                        class="mb-12 flex flex-col gap-5 md:flex-row md:items-end md:justify-between"
                    >
                        <div>
                            <p
                                class="font-mono text-xs font-bold tracking-[0.2em] text-[#ee4f87] uppercase"
                            >
                                živý náhled
                            </p>
                        </div>
                    </div>

                    <div
                        class="overflow-hidden rounded-[2rem] border-2 border-[#18243d] bg-white shadow-[12px_14px_0_#18243d]"
                    >
                        <div
                            class="flex items-center gap-3 border-b-2 border-[#18243d] bg-[#dff5df] px-5 py-3"
                        >
                            <div class="flex gap-2">
                                <span
                                    class="size-3 rounded-full border border-[#18243d] bg-[#ee4f87]"
                                ></span>
                                <span
                                    class="size-3 rounded-full border border-[#18243d] bg-[#ffd04a]"
                                ></span>
                                <span
                                    class="size-3 rounded-full border border-[#18243d] bg-[#65c98b]"
                                ></span>
                            </div>
                            <div
                                class="mx-auto rounded-full border border-[#18243d]/20 bg-white/70 px-5 py-1 font-mono text-[10px] text-[#62697a]"
                            >
                                capylendar.laravel.cloud/dashboard
                            </div>
                        </div>

                        <div class="max-h-[48rem] overflow-auto">
                            <AuthenticatedLayout
                                :display-floating-action-button="false"
                            >
                                <UTabs :items="previewTabs">
                                    <template #upcoming>
                                        <DashboardList
                                            heading="Nadcházející"
                                            :events="upcomingEvents"
                                            :todos="upcomingTodos"
                                            :create-if-empty="true"
                                            @toggled="handleToggled"
                                        />
                                    </template>

                                    <template #history>
                                        <EventsList
                                            heading="Historické"
                                            :events="historyEvents"
                                            :create-event-if-empty="true"
                                        />
                                    </template>
                                </UTabs>
                            </AuthenticatedLayout>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-y border-[#18243d]/15 py-20 md:py-28">
                <div class="mx-auto max-w-7xl px-6 lg:px-10">
                    <div class="mb-12 max-w-3xl">
                        <p
                            class="font-mono text-xs font-bold tracking-[0.2em] text-[#ee4f87] uppercase"
                        >
                            všechno důležité
                        </p>
                        <h2
                            class="mt-3 text-4xl leading-none font-black tracking-[-0.045em] md:text-6xl"
                        >
                            Malá aplikace, která zná celý váš týden.
                        </h2>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <article
                            v-for="feature in features"
                            :key="feature.name"
                            class="group min-h-64 rounded-[1.75rem] border-2 border-[#18243d] p-6 transition-transform hover:-translate-y-1"
                            :class="[feature.accent, feature.span]"
                        >
                            <div
                                class="mb-12 flex size-12 items-center justify-center rounded-full border-2 border-[#18243d] bg-[#fffaf2] shadow-[3px_3px_0_#18243d]"
                            >
                                <UIcon :name="feature.icon" class="size-6" />
                            </div>
                            <h3
                                class="max-w-xl text-2xl leading-tight font-black tracking-[-0.025em]"
                            >
                                {{ feature.name }}
                            </h3>
                            <p
                                class="mt-3 max-w-xl leading-relaxed text-[#535c70]"
                            >
                                {{ feature.description }}
                            </p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="bg-[#18243d] py-20 text-white md:py-28">
                <div class="mx-auto max-w-7xl px-6 lg:px-10">
                    <div
                        class="grid gap-14 lg:grid-cols-[0.8fr_1.2fr] lg:items-end"
                    >
                        <div>
                            <p
                                class="font-mono text-xs font-bold tracking-[0.2em] text-[#ffd04a] uppercase"
                            >
                                pod kapotou
                            </p>
                            <h2
                                class="mt-3 text-4xl leading-none font-black tracking-[-0.045em] md:text-6xl"
                            >
                                Moderní monolit bez servisního cirkusu.
                            </h2>
                            <p class="mt-6 max-w-xl text-lg text-white/60">
                                Typově bezpečný frontend, Laravel backend a
                                jeden deploy. CI hlídá testy, statickou analýzu,
                                frontend i balíčkové audity.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div
                                v-for="technology in stack"
                                :key="technology.name"
                                class="flex min-h-32 flex-col justify-between rounded-2xl border border-white/15 bg-white/5 p-4 transition-colors hover:bg-white/10"
                            >
                                <UIcon :name="technology.icon" class="size-8" />
                                <span class="font-bold">{{
                                    technology.name
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-[#ffd04a] py-16 md:py-20">
                <div
                    class="mx-auto flex max-w-7xl flex-col gap-10 px-6 md:flex-row md:items-center md:justify-between lg:px-10"
                >
                    <div class="flex items-center gap-5">
                        <img
                            src="https://github.com/koubevo.png"
                            alt="Vojtěch Koubek"
                            class="size-20 rounded-full border-2 border-[#18243d] object-cover shadow-[5px_5px_0_#18243d]"
                        />
                        <div>
                            <p class="font-mono text-xs uppercase">
                                navrhl a postavil
                            </p>
                            <h2 class="mt-1 text-3xl font-black">
                                Vojtěch Koubek
                            </h2>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a
                            :href="linkedinUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="rounded-full border-2 border-[#18243d] bg-[#fffaf2] px-5 py-3 font-bold transition-transform hover:-translate-y-1"
                        >
                            LinkedIn
                        </a>
                        <a
                            :href="emailUrl"
                            class="rounded-full border-2 border-[#18243d] bg-[#18243d] px-5 py-3 font-bold text-white transition-transform hover:-translate-y-1"
                        >
                            Napsat e-mail
                        </a>
                    </div>
                </div>
            </section>

            <footer class="bg-[#0f1729] py-8 text-white/55">
                <div
                    class="mx-auto flex max-w-7xl flex-col gap-3 px-6 text-sm md:flex-row md:items-center md:justify-between lg:px-10"
                >
                    <p>Postaveno pro dva. Testováno na kapybarách.</p>
                </div>
            </footer>
        </main>
    </GuestLayout>
</template>

<style scoped>
.paper-grid {
    background-color: #f4efe6;
    background-image:
        linear-gradient(rgba(24, 36, 61, 0.055) 1px, transparent 1px),
        linear-gradient(90deg, rgba(24, 36, 61, 0.055) 1px, transparent 1px);
    background-size: 32px 32px;
}
</style>
