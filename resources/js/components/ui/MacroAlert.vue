<script setup lang="ts">

interface Props {
   label: string;
   icon: string;
}

const props = defineProps<Props>();
</script>

<template>
    <div
        class="mt-2 flex items-center gap-2 rounded-md border border-gray-200 bg-pink-100 p-3 text-sm transition-all"
    >
        <UIcon :name="props.icon" />
        <span>{{props.label}}</span>
    </div>
</template>

<style scoped>

</style>
