<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <UFooter>
        <div class="flex flex-col items-center justify-between">
            <UBadge :label="'Verze: ' + page.props.version" class="mb-2" />
            <ULink to="/logout" method="post" class="mb-2"> Odhlásit se </ULink>
            <p class="mb-2 text-sm text-muted">
                Capylendar by HLBPRG © {{ new Date().getFullYear() }}
            </p>
        </div>
    </UFooter>
</template>
