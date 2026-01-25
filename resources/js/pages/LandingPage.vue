<script setup lang="ts">
import EventsList from '@/components/events/EventsList.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import GuestLayout from '@/layouts/app/GuestLayout.vue';
import { usePage } from '@inertiajs/vue3';

const githubUrl = 'https://github.com/koubevo/capylendar';
const linkedinUrl = 'https://linkedin.com/in/koubevo';
const emailUrl = 'mailto:vojtech.koubek@seznam.cz';
const laravelCloudUrl = 'https://cloud.laravel.com';

const page = usePage();
const stack = [
    { name: 'Laravel 12', desc: 'Core Framework', icon: 'i-logos-laravel' },
    { name: 'Vue 3', desc: 'Frontend Engine', icon: 'i-logos-vue' },
    {
        name: 'TypeScript',
        desc: 'Type Safety',
        icon: 'i-logos-typescript-icon',
    },
    {
        name: 'Inertia.js',
        desc: 'The Monolith',
        icon: 'simple-icons:inertia',
        color: 'text-purple-600',
    },
    { name: 'Tailwind', desc: 'Utility CSS', icon: 'i-logos-tailwindcss-icon' },
    { name: 'PostgreSQL', desc: 'Persistence', icon: 'i-logos-postgresql' },
];

const tooling = [
    { name: 'Sail', icon: 'i-logos-docker-icon' },
    { name: 'Pint', icon: 'i-lucide-code', color: 'text-pink-600' },
    {
        name: 'Pest',
        icon: 'i-lucide-test-tube-diagonal',
        color: 'text-purple-600',
    },
    { name: 'Telescope', icon: 'i-lucide-telescope', color: 'text-blue-500' },
    { name: 'Vite', icon: 'i-logos-vitejs' },
    { name: 'PHPStan', icon: null },
    { name: 'PHPStorm', icon: 'i-logos-phpstorm' },
    { name: 'YouTrack', icon: 'i-logos-youtrack' },
    { name: 'Laravel Cloud', icon: 'i-lucide-cloud', color: 'text-blue-800' },
];

const items = [
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

const features = [
    {
        name: 'Sdílený kalendář',
        desc: 'Eventy vidíte oba. Barevně odlišené podle partnera.',
        icon: 'i-lucide-calendar-days',
    },
    {
        name: 'Push notifikace',
        desc: 'Denní připomínky ráno i večer přímo do prohlížeče.',
        icon: 'i-lucide-bell-ring',
    },
    {
        name: 'Vlastní tagy',
        desc: 'Kategorizujte eventy podle typu a důležitosti.',
        icon: 'i-lucide-tags',
    },
    {
        name: 'PWA aplikace',
        desc: 'Nainstalujte si jako aplikaci na mobil i desktop.',
        icon: 'i-lucide-smartphone',
    },
];

const upcomingEvents = [
    {
        id: 1,
        title: 'Narozeniny',
        date: {
            key: new Date().toISOString().split('T')[0],
            label: 'dnes',
            start_time: '',
            end_time: '',
            is_all_day: true,
        },
        capybara: {
            value: 'pink',
            label: 'Stacy',
            classes: 'bg-pink-100 md:bg-pink-50 hover:bg-pink-100',
            avatar: {
                src: '/images/capys/pink.jpg',
                alt: 'Pink',
            },
        },
        is_private: false,
        has_hearts: true,
        created_at_human: 'dnes',
        author: {
            capybara: 'pink',
            id: '2',
            name: 'Stacy',
        },
        has_map_meta: false,
        tags: [
            {
                id: 1,
                label: 'Oslava',
                color: '#f6339a',
                created_at: '2025-12-12T16:10:31.000000Z',
                updated_at: '2025-12-12T16:10:31.000000Z',
                pivot: {
                    event_id: 63,
                    tag_id: 1,
                },
            },
        ],
    },
    {
        id: 2,
        title: 'Oční',
        date: {
            key: new Date(new Date().setDate(new Date().getDate() + 1))
                .toISOString()
                .split('T')[0],
            label: 'zítra',
            start_time: '12:00',
            end_time: '12:30',
            is_all_day: false,
        },
        capybara: {
            value: 'blue',
            label: 'John',
            classes: 'bg-blue-100 md:bg-blue-50 hover:bg-blue-100',
            avatar: {
                src: '/images/capys/blue.jpg',
                alt: 'Blue',
            },
        },
        is_private: true,
        has_hearts: false,
        created_at_human: 'dnes',
        author: {
            capybara: 'blue',
            id: '3',
            name: 'John',
        },
        has_map_meta: false,
        tags: [
            {
                id: 1,
                label: 'Doktor',
                color: '#00918a',
                created_at: '2025-12-12T16:10:31.000000Z',
                updated_at: '2025-12-12T16:10:31.000000Z',
                pivot: {
                    event_id: 63,
                    tag_id: 1,
                },
            },
        ],
    },
    {
        id: 3,
        title: 'Večeře',
        date: {
            key: new Date(new Date().setDate(new Date().getDate() + 1))
                .toISOString()
                .split('T')[0],
            label: 'zítra',
            start_time: '20:00',
            is_all_day: false,
        },
        capybara: {
            value: 'yellow',
            label: 'Oba',
            classes: 'bg-yellow-100 md:bg-yellow-50 hover:bg-yellow-100',
            avatar: {
                src: '/images/capys/yellow.jpg',
                alt: 'Yellow',
            },
        },
        is_private: false,
        has_hearts: false,
        created_at_human: 'dnes',
        author: {
            capybara: 'yellow',
            id: '3',
            name: 'John',
        },
        has_map_meta: false,
    },
];

const historyEvents = [];
</script>

<template>
    <GuestLayout>
        <div
            class="bg-white font-sans text-gray-900 selection:bg-gray-900 selection:text-white"
        >
            <section
                class="relative overflow-hidden border-b border-gray-100 pt-16 pb-24 md:pt-32"
            >
                <div
                    class="relative z-10 mx-auto max-w-[110rem] px-6 text-center lg:px-12"
                >
                    <div
                        class="mb-8 inline-flex items-center gap-2 rounded-full border border-gray-200 bg-gray-50/50 px-3 py-1 shadow-sm backdrop-blur-sm"
                    >
                        <div class="relative flex h-2 w-2">
                            <span
                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                            ></span>
                            <span
                                class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"
                            ></span>
                        </div>
                        <span
                            class="text-[11px] font-bold tracking-widest text-gray-500 uppercase"
                            >v{{ page.props.version }} • Production Ready</span
                        >
                    </div>

                    <h1
                        class="mb-8 text-6xl leading-[0.9] font-bold tracking-tighter text-gray-900 md:text-8xl lg:text-[7rem]"
                    >
                        Shared
                        <span class="whitespace-nowrap">
                            Ca<span class="text-primary-500">(py)</span>lendar.
                        </span>
                        <br />
                    </h1>

                    <p
                        class="mx-auto mb-12 max-w-3xl text-xl leading-relaxed font-light text-gray-500 md:text-2xl"
                    >
                        Moderní monolit bez kompromisů.
                        <span class="font-medium text-gray-900"
                            >Capylendar</span
                        >
                        demonstruje sílu Laravelu 12 v kombinaci s reaktivitou
                        Vue 3. Žádné zbytečné API, jen čistý výkon.
                    </p>

                    <div
                        class="flex flex-col items-center justify-center gap-4 sm:flex-row"
                    >
                        <a
                            :href="githubUrl"
                            target="_blank"
                            class="flex transform items-center gap-2 rounded-xl bg-black px-8 py-4 font-bold text-white shadow-xl shadow-gray-200/50 transition-colors duration-200 hover:-translate-y-0.5 hover:bg-gray-800"
                        >
                            <UIcon name="i-lucide-github" class="size-5" />
                            <span>Zdrojový kód</span>
                        </a>
                        <a
                            :href="laravelCloudUrl"
                            target="_blank"
                            class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-8 py-4 font-bold text-gray-900 transition-all hover:border-gray-400 hover:bg-gray-50"
                        >
                            <UIcon name="i-lucide-cloud" class="size-5" />
                            <span>Laravel Cloud</span>
                        </a>
                    </div>
                </div>

                <div class="mx-auto mt-16 max-w-[100rem] px-4 md:mt-32 lg:px-8">
                    <div
                        class="group relative flex aspect-[9/16] flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-[0_20px_60px_-15px_rgba(0,0,0,0.1)] md:aspect-[16/10]"
                    >
                        <div
                            class="flex h-11 shrink-0 items-center gap-2 border-b border-gray-200 bg-gray-50 px-4"
                        >
                            <div
                                class="flex gap-1.5 opacity-30 transition-opacity group-hover:opacity-100"
                            >
                                <div
                                    class="h-3 w-3 rounded-full bg-red-400"
                                ></div>
                                <div
                                    class="h-3 w-3 rounded-full bg-yellow-400"
                                ></div>
                                <div
                                    class="h-3 w-3 rounded-full bg-green-400"
                                ></div>
                            </div>
                            <div
                                class="ml-4 flex h-7 max-w-md flex-1 items-center justify-center rounded border border-gray-200 bg-white font-mono text-[11px] text-gray-400 shadow-sm"
                            >
                                <UIcon
                                    name="i-lucide-lock"
                                    class="mr-1.5 size-3 opacity-50"
                                />
                                app.capylendar.com/dashboard
                            </div>
                        </div>

                        <AuthenticatedLayout
                            :displayFloatingActionButton="false"
                        >
                            <UTabs :items="items">
                                <template #upcoming>
                                    <EventsList
                                        heading="Nadcházející"
                                        :events="upcomingEvents"
                                        :create-event-if-empty="true"
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
            </section>

            <section
                class="border-t border-gray-100 bg-gray-50/30 py-16 md:py-24"
            >
                <div class="mx-auto max-w-[110rem] px-6 lg:px-12">
                    <div class="mb-12 text-center">
                        <h2
                            class="text-4xl font-bold tracking-tight text-gray-900"
                        >
                            Funkce
                        </h2>
                        <p class="mt-2 text-lg text-gray-500">
                            Vše, co potřebujete pro sdílený kalendář.
                        </p>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                        <div
                            v-for="feature in features"
                            :key="feature.name"
                            class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition-all hover:shadow-md"
                        >
                            <div
                                class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 transition-colors group-hover:bg-primary-100"
                            >
                                <UIcon
                                    :name="feature.icon"
                                    class="size-6 text-gray-600 transition-colors group-hover:text-primary-600"
                                />
                            </div>
                            <h3 class="mb-2 text-lg font-bold text-gray-900">
                                {{ feature.name }}
                            </h3>
                            <p class="text-sm text-gray-500">
                                {{ feature.desc }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-white py-16 md:py-32">
                <div class="mx-auto max-w-[110rem] px-6 lg:px-12">
                    <div
                        class="mb-12 flex flex-col items-end justify-between pb-8 md:flex-row"
                    >
                        <div>
                            <h2
                                class="text-4xl font-bold tracking-tight text-gray-900"
                            >
                                The Stack
                            </h2>
                            <p class="mt-2 text-lg text-gray-500">
                                Technologický základ zvolený pro stabilitu a
                                typovou bezpečnost.
                            </p>
                        </div>
                    </div>

                    <div
                        class="mb-20 grid grid-cols-2 border-t border-l border-gray-100 md:grid-cols-3 lg:grid-cols-6"
                    >
                        <div
                            v-for="tech in stack"
                            :key="tech.name"
                            class="group border-r border-b border-gray-100 p-8 transition-colors hover:bg-gray-50/50"
                        >
                            <UIcon
                                :name="tech.icon"
                                class="mb-6 size-10 opacity-60 grayscale transition-all group-hover:opacity-100 group-hover:grayscale-0"
                            />
                            <div class="text-base font-bold text-gray-900">
                                {{ tech.name }}
                            </div>
                            <div
                                class="mt-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase"
                            >
                                {{ tech.desc }}
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col items-center justify-center">
                        <p
                            class="mb-8 text-xs font-bold tracking-widest text-gray-400 uppercase"
                        >
                            Powered by Ecosystem
                        </p>
                        <div
                            class="flex flex-wrap justify-center gap-8 opacity-70 grayscale transition-all duration-500 hover:grayscale-0 md:gap-16"
                        >
                            <div
                                v-for="tool in tooling"
                                :key="tool.name"
                                class="group flex cursor-default items-center gap-2"
                            >
                                <svg
                                    v-if="tool.name === 'PHPStan'"
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="32"
                                    height="32"
                                    viewBox="0 0 32 32"
                                >
                                    <title>File-type-phpstan SVG Icon</title>
                                    <path
                                        fill="#232323"
                                        d="m6.405 24.179l-.636 1.506a1.008 1.008 0 0 0 .547 1.269a1.031 1.031 0 0 0 1.316-.348l.218-.521Z"
                                    />
                                    <path
                                        fill="#516cb3"
                                        d="M16.08 4.6a7.47 7.47 0 0 1 4.47 1.5a.408.408 0 0 0 .536 0a.385.385 0 0 0 .029-.538a1.747 1.747 0 0 0-.9-.593a3.11 3.11 0 0 1 2.531-.961c2.323 0 7.258 3.374 7.258 9.7c0 6.074-3.977 7.383-5.058 7.383c-1.645 0-3.351-1.7-3.351-2.781a4.886 4.886 0 0 0 2.363-3.32s.087-2.591 0-4.786a.462.462 0 0 0-.138-.254a.457.457 0 0 0-.261-.121a.412.412 0 0 0-.294.117a.418.418 0 0 0-.126.292c0 .245.108 1.762.027 4.486a3.66 3.66 0 0 1-2.086 2.87a15.766 15.766 0 0 0 .528-1.827c.051-.239 0-.405-.159-.473a.353.353 0 0 0-.4.08a.357.357 0 0 0-.076.123c-.107.215-3.885 12.5-9.111 12.5c-4.415 0-6.96-6.3-6.96-7.745a1.884 1.884 0 0 1 1.734-2.126A3.329 3.329 0 0 1 8.688 19.2c.135.26-.582.883-.582.883l-.625-.5a.384.384 0 0 0-.488.014a.364.364 0 0 0-.01.511c.159.16 4.932 4.006 4.932 4.006a.445.445 0 0 0 .568.037a.377.377 0 0 0 .017-.529l-1.344-1.083l1.432-4.03s-2.2-.982-3.632.1a2.79 2.79 0 0 0-2.261-1.261a2.5 2.5 0 0 0-1.738.666a2.537 2.537 0 0 0-.808 1.686A8.647 8.647 0 0 1 2 13.741c0-4.378 3.467-9.735 7.487-9.735a3.916 3.916 0 0 1 2.488.889l-.888.675a.407.407 0 0 0-.03.558a.377.377 0 0 0 .507.051A6.486 6.486 0 0 1 16.08 4.6"
                                    />
                                    <path
                                        fill="#232323"
                                        d="m9.853 21.5l1.273-2.884a9.495 9.495 0 0 0 1.543.16a5.887 5.887 0 0 0 5.761-5.939a6 6 0 0 0-12-.194a5.826 5.826 0 0 0 2.7 5.14l-1.024 2.309Zm9.908-8.49a.318.318 0 0 1-.27-.149a.322.322 0 0 1-.018-.309a1.5 1.5 0 0 1 1.244-.849a1.483 1.483 0 0 1 1.241.8a.322.322 0 0 1 .025.248a.32.32 0 0 1-.159.191a.318.318 0 0 1-.432-.148a.848.848 0 0 0-.675-.452c-.433 0-.666.481-.67.481a.318.318 0 0 1-.286.186Z"
                                    />
                                    <path
                                        fill="#d2d2d2"
                                        d="M12.425 16.7a3.86 3.86 0 1 0-3.832-3.86a3.846 3.846 0 0 0 3.832 3.86"
                                    />
                                    <path
                                        fill="#232323"
                                        d="M12.425 14.834a1.992 1.992 0 1 0-1.978-1.992a1.985 1.985 0 0 0 1.978 1.992"
                                    />
                                </svg>

                                <UIcon
                                    v-else
                                    :name="tool.icon"
                                    class="size-6 transition-transform group-hover:scale-110"
                                    :class="tool.color"
                                />
                                <span
                                    class="text-sm font-bold text-gray-600 transition-colors group-hover:text-gray-900"
                                    >{{ tool.name }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-t border-gray-100 py-16">
                <div class="mx-auto max-w-5xl px-6 text-center">
                    <div
                        class="mx-auto mb-8 h-24 w-24 overflow-hidden rounded-full bg-gray-200 shadow-inner"
                    >
                        <img
                            src="https://github.com/koubevo.png"
                            class="h-full w-full object-cover"
                            alt="Vojta"
                        />
                    </div>

                    <h2
                        class="mb-6 text-3xl font-bold text-gray-900 md:text-4xl"
                    >
                        Vojtěch Koubek
                    </h2>
                    <p
                        class="mx-auto mb-10 max-w-3xl text-xl leading-relaxed font-light text-gray-600"
                    >
                        Jsem doma hlavně v Laravelu, nyní pracuju i s Node.js a
                        React. <br class="hidden md:block" />
                        Jsem otevřený freelance nabídkám v ekosystému Laravelu.
                    </p>

                    <div class="flex justify-center gap-6 text-sm font-bold">
                        <a
                            :href="linkedinUrl"
                            target="_blank"
                            class="flex items-center gap-2 text-gray-900 transition-colors hover:text-purple-600"
                        >
                            LinkedIn
                        </a>
                        <a
                            :href="githubUrl"
                            target="_blank"
                            class="flex items-center gap-2 text-gray-900 transition-colors hover:text-purple-600"
                        >
                            GitHub
                        </a>
                        <a
                            :href="emailUrl"
                            class="flex items-center gap-2 text-gray-900 transition-colors hover:text-purple-600"
                        >
                            vojtech.koubek@seznam.cz
                        </a>
                    </div>
                </div>
            </section>

            <footer
                class="border-t border-gray-900 bg-[#18181b] py-20 text-sm text-gray-400 antialiased"
            >
                <div class="mx-auto max-w-[110rem] px-6 lg:px-12">
                    <div class="mb-20 grid grid-cols-2 gap-12 md:grid-cols-12">
                        <div class="col-span-2 pr-10 md:col-span-4">
                            <div
                                class="mb-6 flex items-center gap-2 text-xl font-bold text-white"
                            >
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-black"
                                >
                                    <UIcon name="i-lucide-box" class="size-5" />
                                </div>
                                Capylendar
                            </div>
                            <p
                                class="mb-8 text-base leading-relaxed text-gray-500"
                            >
                                Showcase projekt, který sám na denní bázi
                                používám a demonstruje best practices v
                                ekosystému Laravel, Inertia a Vue.
                            </p>
                        </div>

                        <div class="col-span-1 md:col-span-2 md:col-start-6">
                            <h4 class="mb-6 font-bold text-white">Project</h4>
                            <ul class="space-y-4">
                                <li>
                                    <a
                                        :href="githubUrl"
                                        class="transition-colors hover:text-white"
                                        >Source Code</a
                                    >
                                </li>
                            </ul>
                        </div>

                        <div class="col-span-1 md:col-span-2">
                            <h4 class="mb-6 font-bold text-white">Ecosystem</h4>
                            <ul class="space-y-4">
                                <li>
                                    <a
                                        href="https://laravel.com"
                                        class="transition-colors hover:text-white"
                                        target="_blank"
                                    >
                                        Laravel
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="https://vuejs.org"
                                        class="transition-colors hover:text-white"
                                        target="_blank"
                                        >Vue.js</a
                                    >
                                </li>
                                <li>
                                    <a
                                        href="https://inertiajs.com"
                                        class="transition-colors hover:text-white"
                                        target="_blank"
                                        >Inertia</a
                                    >
                                </li>
                            </ul>
                        </div>

                        <div class="col-span-2 md:col-span-2">
                            <h4 class="mb-6 font-bold text-white">Contact</h4>
                            <ul class="space-y-4">
                                <li>
                                    <a
                                        :href="emailUrl"
                                        class="transition-colors hover:text-white"
                                        >vojtech.koubek@seznam.cz</a
                                    >
                                </li>
                                <li>Munich, Germany & Prague, Czechia</li>
                            </ul>
                        </div>
                    </div>

                    <div
                        class="flex flex-col items-center justify-between gap-6 border-t border-white/10 pt-8 md:flex-row"
                    >
                        <div class="text-sm text-gray-600">
                            &copy; {{ new Date().getFullYear() }} Vojtěch
                            Koubek. All rights reserved.
                        </div>

                        <div
                            class="flex cursor-default items-center gap-3 rounded-full border border-white/10 bg-white/5 px-4 py-2 transition-colors hover:bg-white/10"
                        >
                            <div class="relative flex h-2.5 w-2.5">
                                <span
                                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-500 opacity-75"
                                ></span>
                                <span
                                    class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"
                                ></span>
                            </div>
                            <span
                                class="text-xs font-bold tracking-wide text-emerald-400 uppercase"
                                >Open for freelance opportunities</span
                            >
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </GuestLayout>
</template>
