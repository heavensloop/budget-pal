<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';

defineProps<{
    icon: LucideIcon;
    iconColor?: 'primary' | 'danger';
    badgeText: string;
    badgeColor?: 'success' | 'pending' | 'danger';
    value: string;
    label: string;
}>();
</script>

<template>
    <div class="box p-5">
        <div class="flex">
            <component
                :is="icon"
                class="size-7 stroke-1"
                :class="iconColor === 'danger' ? 'text-danger' : 'text-primary'"
            />
            <div class="ms-auto">
                <div
                    class="flex items-center rounded-full border px-2 py-px text-xs"
                    :class="{
                        'border-success/60 bg-success/15 text-success':
                            badgeColor === 'success',
                        'border-pending/60 bg-pending/15 text-pending':
                            badgeColor === 'pending',
                        'border-danger/60 bg-danger/15 text-danger':
                            badgeColor === 'danger',
                    }"
                >
                    {{ badgeText }}
                </div>
            </div>
        </div>
        <div class="mt-6 text-2xl leading-8 font-medium">{{ value }}</div>
        <div class="mt-1.5 text-xs uppercase opacity-70">{{ label }}</div>
    </div>
</template>
