<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Bell,
    CircleGauge,
    CreditCard,
    LineChart,
    ListChecks,
    MoveRight,
    PiggyBank,
    Settings,
    ShoppingBag,
} from '@lucide/vue';
import BrandMark from '@/components/BrandMark.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';
import { index as debtsIndex } from '@/routes/debts';
import { index as needsIndex } from '@/routes/needs';
import { edit as editProfile } from '@/routes/profile';
import type { NavItem } from '@/types';

defineProps<{
    open: boolean;
}>();

defineEmits<{
    close: [];
}>();

type SoonItem = { title: string; icon: NavItem['icon'] };

const planningItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: CircleGauge },
    { title: 'Needs', href: needsIndex(), icon: ListChecks },
    { title: 'Debts', href: debtsIndex(), icon: CreditCard },
];

const planningSoon: SoonItem[] = [
    { title: 'Wants', icon: ShoppingBag },
    { title: 'Savings & Investments', icon: PiggyBank },
];

const insightsSoon: SoonItem[] = [
    { title: 'Review & Insights', icon: LineChart },
    { title: 'Reminders', icon: Bell },
];

const accountItems: NavItem[] = [
    { title: 'Settings', href: editProfile(), icon: Settings },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div
        class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm xl:hidden"
        :class="open ? 'block' : 'hidden'"
        @click="$emit('close')"
    />

    <div
        class="fixed top-0 left-0 z-50 h-screen w-68.75 flex-none bg-primary text-primary-foreground transition-transform duration-200 xl:translate-x-0"
        :class="open ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="flex h-screen flex-col pt-5 pb-8">
            <Link
                :href="dashboard()"
                class="flex h-16.25 flex-none items-center px-6"
            >
                <BrandMark class="size-6" />
                <div class="ml-3.5 text-nowrap">
                    <span class="text-base font-medium">Budget</span>
                    <span class="text-base font-light">Pal</span>
                </div>
            </Link>

            <nav class="mt-2 flex-1 overflow-y-auto px-4">
                <div class="side-menu__group-label">Planning</div>
                <Link
                    v-for="item in planningItems"
                    :key="item.title"
                    :href="item.href"
                    class="side-menu__link"
                    :class="{
                        'side-menu__link--active': isCurrentOrParentUrl(
                            item.href,
                        ),
                    }"
                >
                    <component
                        :is="item.icon"
                        class="side-menu__link__icon size-4 stroke-[1.5]"
                    />
                    <div class="side-menu__link__title">{{ item.title }}</div>
                </Link>
                <div
                    v-for="item in planningSoon"
                    :key="item.title"
                    class="side-menu__link cursor-not-allowed opacity-50"
                >
                    <component
                        :is="item.icon"
                        class="side-menu__link__icon size-4 stroke-[1.5]"
                    />
                    <div class="side-menu__link__title flex-1">
                        {{ item.title }}
                    </div>
                    <span class="text-[10px] opacity-70">Soon</span>
                </div>

                <div class="side-menu__group-label">Insights</div>
                <div
                    v-for="item in insightsSoon"
                    :key="item.title"
                    class="side-menu__link cursor-not-allowed opacity-50"
                >
                    <component
                        :is="item.icon"
                        class="side-menu__link__icon size-4 stroke-[1.5]"
                    />
                    <div class="side-menu__link__title flex-1">
                        {{ item.title }}
                    </div>
                    <span class="text-[10px] opacity-70">Soon</span>
                </div>

                <div class="side-menu__group-label">Account</div>
                <Link
                    v-for="item in accountItems"
                    :key="item.title"
                    :href="item.href"
                    class="side-menu__link"
                    :class="{
                        'side-menu__link--active': isCurrentOrParentUrl(
                            item.href,
                        ),
                    }"
                >
                    <component
                        :is="item.icon"
                        class="side-menu__link__icon size-4 stroke-[1.5]"
                    />
                    <div class="side-menu__link__title">{{ item.title }}</div>
                </Link>
            </nav>

            <Link
                :href="editProfile()"
                class="mx-4 flex items-center rounded-full border border-primary-foreground/20 bg-primary-foreground/10 p-2.5 opacity-80 backdrop-blur transition hover:opacity-100"
            >
                <div
                    class="bp-avatar h-10 w-10 flex-none rounded-full border-4 border-primary-foreground/20 bg-primary-foreground/20"
                >
                    <slot name="avatar" />
                </div>
                <div class="ms-3 flex w-full items-center overflow-hidden">
                    <div class="w-28">
                        <slot name="account" />
                    </div>
                    <MoveRight class="ms-auto me-4 size-4 opacity-50" />
                </div>
            </Link>
        </div>
    </div>
</template>
