<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <svg viewBox="0 0 400 220" fill="none" :class="props.class">
        <circle
            cx="200"
            cy="110"
            r="65"
            stroke="currentColor"
            stroke-width="40"
        />
        <rect
            x="70"
            y="165"
            width="260"
            height="40"
            rx="20"
            ry="20"
            fill="currentColor"
        />
    </svg>
</template>
