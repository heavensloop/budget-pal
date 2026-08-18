<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import BrandMark from '@/components/BrandMark.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center bg-muted p-6 md:p-10"
    >
        <div class="box w-full max-w-105 p-9 shadow-2xl shadow-primary/20">
            <Link
                :href="home()"
                class="mb-7 flex flex-col items-center gap-2.5"
            >
                <BrandMark class="size-14 text-primary" />
                <div class="text-xl font-extrabold tracking-tight">
                    <span class="text-primary">Budget</span>
                    <span class="text-pending">Pal</span>
                </div>
            </Link>

            <div class="mb-7 space-y-1 text-center">
                <h1 class="text-xl font-extrabold">{{ title }}</h1>
                <p class="text-sm opacity-60">{{ description }}</p>
            </div>

            <slot />
        </div>
    </div>
</template>
