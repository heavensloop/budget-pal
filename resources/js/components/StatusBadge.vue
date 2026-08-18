<script setup lang="ts">
import { Check, Clock, X } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{
    status: 'done' | 'pending' | 'skipped';
}>();

const config = computed(
    () =>
        ({
            done: {
                icon: Check,
                label: 'Done',
                class: 'text-success bg-success/15',
            },
            pending: {
                icon: Clock,
                label: 'Pending',
                class: 'text-pending bg-pending/15',
            },
            skipped: {
                icon: X,
                label: 'Skipped',
                class: 'text-foreground/55 bg-foreground/8',
            },
        })[props.status],
);
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold"
        :class="config.class"
    >
        <component :is="config.icon" class="size-3" stroke-width="3" />
        {{ config.label }}
    </span>
</template>
