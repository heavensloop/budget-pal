<script setup lang="ts">
import { Calendar } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';

export type ReminderItem = {
    title: string;
    subtitle: string;
    tag: string;
    icon?: LucideIcon;
};

defineProps<{
    reminders: ReminderItem[];
}>();
</script>

<template>
    <div v-if="reminders.length" class="mt-4 flex flex-col gap-1">
        <div
            v-for="reminder in reminders"
            :key="reminder.title"
            class="-mx-2 flex items-center gap-3.5 rounded-2xl p-2 hover:bg-foreground/5"
        >
            <span
                class="flex size-10 flex-none items-center justify-center rounded-full bg-primary/10 text-primary"
            >
                <component
                    :is="reminder.icon ?? Calendar"
                    class="size-4 stroke-[1.5]"
                />
            </span>
            <div class="flex flex-1 flex-col">
                <div class="font-medium">{{ reminder.title }}</div>
                <div class="text-xs opacity-60">{{ reminder.subtitle }}</div>
            </div>
            <span class="text-xs opacity-50">{{ reminder.tag }}</span>
        </div>
    </div>
    <div v-else class="py-8 text-center text-xs opacity-50">
        No reminders yet
    </div>
</template>
