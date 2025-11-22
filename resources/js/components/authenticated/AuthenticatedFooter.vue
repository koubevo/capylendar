<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <UFooter>
        <template #left>
            <div class="flex flex-col items-center justify-between">
                <UBadge :label="'Verze: ' + page.props.version" class="mb-2" />
                <ULink
                    to="https://koubevo.youtrack.cloud/form/62436582-9580-4641-badc-fd8a10e9c72d"
                    class="mb-2 underline"
                    target="_blank"
                >
                    Náhlásit problém
                </ULink>
                <ULink to="/logout" method="post" class="mb-2">
                    Odhlásit se
                </ULink>
                <p class="mb-2 text-sm text-muted">
                    Capylendar by HLBPRG © {{ new Date().getFullYear() }}
                </p>
            </div>
        </template>
    </UFooter>
</template>
