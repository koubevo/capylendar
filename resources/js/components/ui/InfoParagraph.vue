<script setup lang="ts">
const props = defineProps<{
    label: string;
    value: string;
}>();
</script>

<template>
    <div>
        <h4 class="text-[10px] font-bold">{{props.label}}</h4>
        {{ props.value }}
    </div>
</template>
