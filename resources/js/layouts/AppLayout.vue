<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import SideMenu from '@/components/SideMenu.vue';
import TopBar from '@/components/TopBar.vue';
import { useInitials } from '@/composables/useInitials';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

const page = usePage();
const { getInitials } = useInitials();
const mobileMenuOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-background">
        <SideMenu :open="mobileMenuOpen" @close="mobileMenuOpen = false">
            <template #avatar>{{
                getInitials(page.props.auth.user.name)
            }}</template>
            <template #account>
                <div class="w-full truncate font-medium">
                    {{ page.props.auth.user.name }}
                </div>
                <div class="w-full truncate text-xs opacity-70">
                    {{ page.props.auth.user.email }}
                </div>
            </template>
        </SideMenu>

        <div class="xl:ml-68.75">
            <div class="px-7 pt-8 pb-12">
                <TopBar @open-menu="mobileMenuOpen = true" />
                <slot />
            </div>
        </div>
    </div>
</template>
