<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    ChevronDown,
    LogOut,
    Menu,
    Settings,
    User,
} from '@lucide/vue';
import { computed } from 'vue';
import NotificationsDropdown from '@/components/NotificationsDropdown.vue';
import { useInitials } from '@/composables/useInitials';
import { useMonthSelector } from '@/composables/useMonthSelector';
import type { CurrentMonth } from '@/composables/useMonthSelector';
import { logout } from '@/routes';
import { edit as editProfile } from '@/routes/profile';

defineEmits<{
    'open-menu': [];
}>();

const page = usePage<{ currentMonth?: CurrentMonth }>();
const currentMonth = computed(() => page.props.currentMonth ?? null);
const { label, goToPreviousMonth, goToNextMonth } =
    useMonthSelector(currentMonth);

const user = computed(() => page.props.auth.user);
const { getInitials } = useInitials();

function handleLogout() {
    router.flushAll();
}
</script>

<template>
    <div class="flex h-16 items-center gap-5 border-b border-foreground/10">
        <button
            type="button"
            class="mr-auto flex size-9 cursor-pointer items-center justify-center rounded-xl border border-foreground/10 xl:hidden"
            @click="$emit('open-menu')"
        >
            <Menu class="size-4 stroke-[1.5]" />
        </button>

        <div
            v-if="currentMonth"
            class="mr-auto hidden items-center gap-1 xl:flex"
        >
            <button
                type="button"
                class="flex size-9 cursor-pointer items-center justify-center rounded-lg border border-foreground/10 hover:bg-foreground/5"
                @click="goToPreviousMonth"
            >
                <ChevronDown class="size-4 rotate-90 stroke-[1.5]" />
            </button>
            <div
                class="inline-flex h-9 items-center gap-2 rounded-lg border border-foreground/10 px-3 text-sm"
            >
                <CalendarDays class="size-4 stroke-[1.5]" />
                {{ label }}
            </div>
            <button
                type="button"
                class="flex size-9 cursor-pointer items-center justify-center rounded-lg border border-foreground/10 hover:bg-foreground/5"
                @click="goToNextMonth"
            >
                <ChevronDown class="size-4 -rotate-90 stroke-[1.5]" />
            </button>
        </div>

        <NotificationsDropdown class="ml-auto" />

        <div class="group/profile relative size-9 flex-none">
            <div
                class="bp-avatar size-full flex-none rounded-full border-3 border-primary/20 bg-primary ring-1 ring-primary/25"
            >
                {{ getInitials(user.name) }}
            </div>
            <div class="hidden group-hover/profile:block">
                <div
                    class="box absolute top-0 right-0 z-50 -mt-0.5 -mr-0.5 flex w-64 flex-col gap-2.5 p-5"
                >
                    <div class="flex flex-col gap-0.5">
                        <div class="font-medium">{{ user.name }}</div>
                        <div class="mt-0.5 text-xs opacity-70">
                            {{ user.email }}
                        </div>
                    </div>
                    <div class="h-px bg-foreground/5" />
                    <div class="flex flex-col gap-0.5">
                        <Link
                            class="-mx-3 flex items-center gap-2.5 rounded-lg px-4 py-1.5 hover:bg-foreground/5"
                            :href="editProfile()"
                        >
                            <User class="size-4 stroke-[1.5]" />
                            Profile
                        </Link>
                        <Link
                            class="-mx-3 flex items-center gap-2.5 rounded-lg px-4 py-1.5 hover:bg-foreground/5"
                            :href="editProfile()"
                        >
                            <Settings class="size-4 stroke-[1.5]" />
                            Settings
                        </Link>
                    </div>
                    <div class="h-px bg-foreground/5" />
                    <div class="flex flex-col gap-0.5">
                        <Link
                            class="-mx-3 flex items-center gap-2.5 rounded-lg px-4 py-1.5 hover:bg-foreground/5"
                            :href="logout()"
                            as="button"
                            @click="handleLogout"
                        >
                            <LogOut class="size-4 stroke-[1.5]" />
                            Logout
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
