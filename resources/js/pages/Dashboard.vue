<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Banknote,
    ListChecks,
    PiggyBank,
    ShoppingBag,
} from '@lucide/vue';
import { computed } from 'vue';
import CategoryBarChart from '@/components/CategoryBarChart.vue';
import ItemTable from '@/components/ItemTable.vue';
import type { RecentItem } from '@/components/ItemTable.vue';
import MeterGauge from '@/components/MeterGauge.vue';
import ReminderList from '@/components/ReminderList.vue';
import type { ReminderItem } from '@/components/ReminderList.vue';
import StatCard from '@/components/StatCard.vue';
import { Button } from '@/components/ui/button';
import { useCurrency } from '@/composables/useCurrency';
import type { CurrentMonth } from '@/composables/useMonthSelector';

type Kpi = { amount: number; currencyCode: string };

type DashboardProps = {
    currentMonth: CurrentMonth;
    hasBudgetMonth: boolean;
    kpis?: {
        income: Kpi;
        needs: Kpi & { paidCount: number; totalCount: number };
        wants: { spent: number; cap: number; currencyCode: string };
        savings: Kpi & { delta: number };
    };
    needsByCategory?: { label: string; amount: number }[];
    recentItems?: RecentItem[];
    reminders?: ReminderItem[];
};

const page = usePage<DashboardProps>();
const { formatCurrency } = useCurrency();

const kpis = computed(
    () =>
        page.props.kpis ?? {
            income: { amount: 0, currencyCode: 'NGN' },
            needs: {
                amount: 0,
                currencyCode: 'NGN',
                paidCount: 0,
                totalCount: 0,
            },
            wants: { spent: 0, cap: 0, currencyCode: 'NGN' },
            savings: { amount: 0, currencyCode: 'NGN', delta: 0 },
        },
);
const needsByCategory = computed(() => page.props.needsByCategory ?? []);
const recentItems = computed(() => page.props.recentItems ?? []);
const reminders = computed(() => page.props.reminders ?? []);

const wantsPct = computed(() => {
    const { spent, cap } = kpis.value.wants;

    return cap > 0 ? Math.round((spent / cap) * 100) : 0;
});

const wantsOverAmount = computed(() => {
    const { spent, cap } = kpis.value.wants;

    return Math.max(spent - cap, 0);
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="mt-8">
        <h1 class="text-2xl font-medium">
            Welcome back, {{ page.props.auth.user.name }}
        </h1>
        <p class="mt-1 opacity-70">
            Here's how {{ page.props.currentMonth.label }} is looking so far.
        </p>
    </div>

    <template v-if="page.props.hasBudgetMonth">
        <div class="mt-5 grid grid-cols-12 gap-6">
            <div class="col-span-12 sm:col-span-6 xl:col-span-3">
                <StatCard
                    :icon="Banknote"
                    badge-color="success"
                    :badge-text="
                        kpis.income.amount > 0 ? 'Received' : 'None yet'
                    "
                    :value="
                        formatCurrency(
                            kpis.income.amount,
                            kpis.income.currencyCode,
                        )
                    "
                    label="Income this month"
                />
            </div>
            <div class="col-span-12 sm:col-span-6 xl:col-span-3">
                <StatCard
                    :icon="ListChecks"
                    badge-color="pending"
                    :badge-text="`${kpis.needs.paidCount} / ${kpis.needs.totalCount} paid`"
                    :value="
                        formatCurrency(
                            kpis.needs.amount,
                            kpis.needs.currencyCode,
                        )
                    "
                    label="Needs this month"
                />
            </div>
            <div class="col-span-12 sm:col-span-6 xl:col-span-3">
                <StatCard
                    :icon="ShoppingBag"
                    :icon-color="wantsPct > 100 ? 'danger' : 'primary'"
                    :badge-color="wantsPct > 100 ? 'danger' : 'success'"
                    :badge-text="
                        wantsPct > 100
                            ? `${wantsPct}% over`
                            : `${wantsPct}% used`
                    "
                    :value="`${formatCurrency(kpis.wants.spent, kpis.wants.currencyCode)}/${formatCurrency(kpis.wants.cap, kpis.wants.currencyCode)}`"
                    label="Wants this month"
                />
            </div>
            <div class="col-span-12 sm:col-span-6 xl:col-span-3">
                <StatCard
                    :icon="PiggyBank"
                    badge-color="success"
                    :badge-text="`+${formatCurrency(kpis.savings.delta, kpis.savings.currencyCode)}`"
                    :value="
                        formatCurrency(
                            kpis.savings.amount,
                            kpis.savings.currencyCode,
                        )
                    "
                    label="Savings this month"
                />
            </div>
        </div>

        <div class="mt-8 grid grid-cols-12 gap-6">
            <div class="col-span-12 lg:col-span-4">
                <div class="box flex h-full flex-col p-6">
                    <h2 class="text-lg font-medium">Wants budget used</h2>
                    <div class="mt-4">
                        <MeterGauge
                            :pct="wantsPct"
                            :capLabel="`of ${formatCurrency(kpis.wants.cap, kpis.wants.currencyCode)} cap`"
                        />
                    </div>
                    <div
                        v-if="wantsOverAmount > 0"
                        class="mt-5 flex items-start gap-2.5 rounded-2xl border border-danger/25 bg-danger/8 p-3.5 text-sm text-danger"
                    >
                        <AlertTriangle
                            class="mt-0.5 size-4 flex-none stroke-[1.5]"
                        />
                        <div>
                            <b
                                >{{
                                    formatCurrency(
                                        wantsOverAmount,
                                        kpis.wants.currencyCode,
                                    )
                                }}
                                over plan.</b
                            >
                            Wants spending has pushed past the configured cap
                            for
                            {{ page.props.currentMonth.label }}.
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 lg:col-span-4">
                <div class="box h-full p-6">
                    <h2 class="text-lg font-medium">Needs by category</h2>
                    <div class="mt-1 text-xs opacity-60">
                        {{ page.props.currentMonth.label }}
                    </div>
                    <div class="mt-4">
                        <CategoryBarChart
                            :labels="needsByCategory.map((c) => c.label)"
                            :amounts="needsByCategory.map((c) => c.amount)"
                            :currency-code="kpis.needs.currencyCode"
                        />
                    </div>
                </div>
            </div>
            <div class="col-span-12 lg:col-span-4">
                <div class="box h-full p-6">
                    <h2 class="text-lg font-medium">Upcoming reminders</h2>
                    <ReminderList :reminders="reminders" />
                </div>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-12 gap-6 pb-10">
            <div class="col-span-12">
                <div class="box p-6">
                    <h2 class="text-lg font-medium">Recent items</h2>
                    <div class="mt-4">
                        <ItemTable :items="recentItems" />
                    </div>
                </div>
            </div>
        </div>
    </template>
    <div
        v-else
        class="box mt-8 flex flex-col items-center justify-center gap-4 py-16 text-center"
    >
        <p class="text-lg font-medium">
            You have not created a budget for
            {{ page.props.currentMonth.label }}
        </p>
        <Button disabled>Set up budget</Button>
    </div>
</template>
