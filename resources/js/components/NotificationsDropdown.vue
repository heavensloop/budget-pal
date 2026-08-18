<script setup lang="ts">
import { Bell } from '@lucide/vue';

defineProps<{
    count?: number;
}>();
</script>

<template>
    <div class="group/notifications relative flex h-9 items-center">
        <div class="relative cursor-pointer">
            <Bell class="size-4 stroke-[1.5] opacity-80" />
            <span
                v-if="count"
                class="absolute -top-1.5 -right-1.5 flex size-4 items-center justify-center rounded-full bg-danger text-[9px] font-medium text-white"
            >
                {{ count }}
            </span>
        </div>
        <div class="hidden group-hover/notifications:block">
            <div
                class="box absolute top-0 right-0 z-50 -mt-0.5 -mr-0.5 flex w-96 flex-col gap-2.5 p-5"
            >
                <div class="flex place-content-between items-center">
                    <div class="font-medium">Notifications</div>
                </div>
                <div class="py-4 text-center text-xs opacity-60">
                    No notifications yet
                </div>
            </div>
        </div>
    </div>
</template>
