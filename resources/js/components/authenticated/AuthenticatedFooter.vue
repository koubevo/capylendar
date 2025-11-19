<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <UFooter>
        <template #left>
            <div class="flex flex-col items-center justify-between">
                <UBadge :label="'Verze: ' + page.props.version" class="mb-2" />
                <p class="text-sm text-muted">
                    Capylendar by HLBPRG © {{ new Date().getFullYear() }}
                </p>
            </div>
        </template>
    </UFooter>
</template>
