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
import { useCurrency } from '@/composables/useCurrency';
import type { CurrentMonth } from '@/composables/useMonthSelector';

type Kpi = { amount: number; currencyCode: string };

type DashboardProps = {
    currentMonth: CurrentMonth;
    kpis: {
        income: Kpi;
        needs: Kpi & { paidCount: number; totalCount: number };
        wants: { spent: number; cap: number; currencyCode: string };
        savings: Kpi & { delta: number };
    };
    needsByCategory: { label: string; amount: number }[];
    recentItems: RecentItem[];
    reminders: ReminderItem[];
};

const page = usePage<DashboardProps>();
const { formatCurrency } = useCurrency();

const wantsPct = computed(() => {
    const { spent, cap } = page.props.kpis.wants;

    return cap > 0 ? Math.round((spent / cap) * 100) : 0;
});

const wantsOverAmount = computed(() => {
    const { spent, cap } = page.props.kpis.wants;

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

    <div class="mt-5 grid grid-cols-12 gap-6">
        <div class="col-span-12 sm:col-span-6 xl:col-span-3">
            <StatCard
                :icon="Banknote"
                badge-color="success"
                :badge-text="
                    page.props.kpis.income.amount > 0 ? 'Received' : 'None yet'
                "
                :value="
                    formatCurrency(
                        page.props.kpis.income.amount,
                        page.props.kpis.income.currencyCode,
                    )
                "
                label="Income this month"
            />
        </div>
        <div class="col-span-12 sm:col-span-6 xl:col-span-3">
            <StatCard
                :icon="ListChecks"
                badge-color="pending"
                :badge-text="`${page.props.kpis.needs.paidCount} / ${page.props.kpis.needs.totalCount} paid`"
                :value="
                    formatCurrency(
                        page.props.kpis.needs.amount,
                        page.props.kpis.needs.currencyCode,
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
                    wantsPct > 100 ? `${wantsPct}% over` : `${wantsPct}% used`
                "
                :value="`${formatCurrency(page.props.kpis.wants.spent, page.props.kpis.wants.currencyCode)}/${formatCurrency(page.props.kpis.wants.cap, page.props.kpis.wants.currencyCode)}`"
                label="Wants this month"
            />
        </div>
        <div class="col-span-12 sm:col-span-6 xl:col-span-3">
            <StatCard
                :icon="PiggyBank"
                badge-color="success"
                :badge-text="`+${formatCurrency(page.props.kpis.savings.delta, page.props.kpis.savings.currencyCode)}`"
                :value="
                    formatCurrency(
                        page.props.kpis.savings.amount,
                        page.props.kpis.savings.currencyCode,
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
                        :capLabel="`of ${formatCurrency(page.props.kpis.wants.cap, page.props.kpis.wants.currencyCode)} cap`"
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
                                    page.props.kpis.wants.currencyCode,
                                )
                            }}
                            over plan.</b
                        >
                        Wants spending has pushed past the configured cap for
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
                        :labels="page.props.needsByCategory.map((c) => c.label)"
                        :amounts="
                            page.props.needsByCategory.map((c) => c.amount)
                        "
                        :currency-code="page.props.kpis.needs.currencyCode"
                    />
                </div>
            </div>
        </div>
        <div class="col-span-12 lg:col-span-4">
            <div class="box h-full p-6">
                <h2 class="text-lg font-medium">Upcoming reminders</h2>
                <ReminderList :reminders="page.props.reminders" />
            </div>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-12 gap-6 pb-10">
        <div class="col-span-12">
            <div class="box p-6">
                <h2 class="text-lg font-medium">Recent items</h2>
                <div class="mt-4">
                    <ItemTable :items="page.props.recentItems" />
                </div>
            </div>
        </div>
    </div>
</template>
