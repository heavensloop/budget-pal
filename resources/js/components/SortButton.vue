<script setup lang="ts">
import { ArrowDown, ArrowUp } from '@lucide/vue';

defineProps<{
    label: string;
    active: boolean;
    direction: 'asc' | 'desc';
    alignEnd?: boolean;
}>();

const emit = defineEmits<{
    click: [];
}>();
</script>

<template>
    <button
        type="button"
        class="flex cursor-pointer items-center gap-1 uppercase hover:opacity-100"
        :class="[
            active ? 'opacity-100' : 'opacity-70',
            alignEnd ? 'ml-auto justify-end' : '',
        ]"
        @click="emit('click')"
    >
        {{ label }}
        <ArrowUp v-if="active && direction === 'asc'" class="size-3" />
        <ArrowDown v-else-if="active && direction === 'desc'" class="size-3" />
    </button>
</template>
