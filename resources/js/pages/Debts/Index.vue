<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { Banknote, CreditCard, BanknoteArrowDown, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import DebtsController from '@/actions/App/Http/Controllers/Web/DebtsController';
import CategoryBarChart from '@/components/CategoryBarChart.vue';
import ScheduleField from '@/components/ScheduleField.vue';
import type {
    RecurrenceOption,
    ScheduleValue,
} from '@/components/ScheduleField.vue';
import StatCard from '@/components/StatCard.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogFooter,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useCurrency } from '@/composables/useCurrency';
import DebtsTable from './DebtsTable.vue';
import type { DebtItem, SortColumn } from './DebtsTable.vue';

type CategoryOption = { value: string; label: string };

const page = usePage<{
    categoryOptions: CategoryOption[];
    items: DebtItem[];
    sort: SortColumn;
    direction: 'asc' | 'desc';
    showArchived: boolean;
    recurrenceOptions: RecurrenceOption[];
}>();

const { formatCurrency } = useCurrency();

const totalBalanceOwed = computed(() =>
    page.props.items.reduce((sum, item) => sum + item.balance, 0),
);

// Debts already paid for the currently active period (canRecordPayment is
// false because of last_payment_date, not because the balance hit 0 -
// paid-off debts are archived and excluded from `items` already) don't
// need another payment until the next period, so they're excluded here.
const itemsAwaitingPayment = computed(() =>
    page.props.items.filter((item) => item.canRecordPayment),
);

const nextPaymentTotal = computed(() =>
    itemsAwaitingPayment.value.reduce(
        (sum, item) => sum + item.monthlyRepaymentAmount,
        0,
    ),
);

const totalInterest = computed(() =>
    page.props.items.reduce(
        (sum, item) => sum + (item.totalRepaymentAmount - item.amountBorrowed),
        0,
    ),
);

const totalMonthlyInterest = computed(() =>
    page.props.items.reduce((sum, item) => sum + item.interestMonthly, 0),
);

const debtsCurrencyCode = computed(
    () => page.props.items[0]?.currencyCode ?? 'NGN',
);

const balanceByCategory = computed(() => {
    const totals = new Map<string, number>();

    for (const item of page.props.items) {
        totals.set(
            item.categoryLabel,
            (totals.get(item.categoryLabel) ?? 0) + item.balance,
        );
    }

    return Array.from(totals, ([label, amount]) => ({ label, amount })).sort(
        (a, b) => b.amount - a.amount,
    );
});

function sortBy(column: SortColumn) {
    const direction =
        page.props.sort === column && page.props.direction === 'asc'
            ? 'desc'
            : 'asc';

    router.get(
        window.location.pathname,
        {
            sort: column,
            direction,
            show_archived: page.props.showArchived ? 1 : 0,
        },
        { preserveState: true, preserveScroll: true },
    );
}

function toggleShowArchived() {
    router.get(
        window.location.pathname,
        {
            sort: page.props.sort,
            direction: page.props.direction,
            show_archived: page.props.showArchived ? 0 : 1,
        },
        { preserveState: true, preserveScroll: true },
    );
}

const dialogOpen = ref(false);
const editingItem = ref<DebtItem | null>(null);
const schedule = ref<ScheduleValue>(null);

// Debts always repeat, so we default to Monthly with no start date chosen
// yet - the user can pick a date or switch recurrence, but "does not
// repeat" isn't an option for this domain.
function defaultSchedule(): ScheduleValue {
    return {
        recurrence: 'monthly',
        startDate: null,
        endDate: null,
        reminderDaysBefore: null,
        intervalMonths: null,
        months: null,
    };
}

function openCreateDialog() {
    editingItem.value = null;
    schedule.value = defaultSchedule();
    dialogOpen.value = true;
}

function openEditDialog(item: DebtItem) {
    editingItem.value = item;
    schedule.value = item.schedule ?? defaultSchedule();
    dialogOpen.value = true;
}

const formAction = computed(() =>
    editingItem.value
        ? DebtsController.update.form(editingItem.value.id)
        : DebtsController.store.form(),
);

function archiveItem(item: DebtItem) {
    router.patch(
        DebtsController.updateStatus(item.id).url,
        { status: 'archived' },
        { preserveScroll: true },
    );
}

function restoreItem(item: DebtItem) {
    router.patch(
        DebtsController.updateStatus(item.id).url,
        { status: 'pending' },
        { preserveScroll: true },
    );
}

function recordPayment(item: DebtItem) {
    router.patch(
        DebtsController.recordPayment(item.id).url,
        {},
        { preserveScroll: true },
    );
}

function destroy(item: DebtItem) {
    if (!confirm(`Delete "${item.name}"?`)) {
        return;
    }

    router.delete(DebtsController.destroy(item.id).url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Debts" />

    <div class="mt-8 flex items-center justify-between">
        <h1 class="text-2xl font-medium">Debts</h1>
        <div class="flex items-center gap-2">
            <Button variant="outline" @click="toggleShowArchived">
                {{
                    page.props.showArchived ? 'Hide Archived' : 'Show Archived'
                }}
            </Button>
            <Button @click="openCreateDialog">
                <Plus class="size-4" />
                Add Debt
            </Button>
        </div>
    </div>

    <div class="mt-6 mb-10">
        <DebtsTable
            :items="page.props.items"
            :sort="page.props.sort"
            :direction="page.props.direction"
            @sort="sortBy"
            @edit="openEditDialog"
            @archive="archiveItem"
            @restore="restoreItem"
            @destroy="destroy"
            @record-payment="recordPayment"
        />

        <div
            v-if="page.props.items.length"
            class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4"
        >
            <StatCard
                :icon="CreditCard"
                :value="formatCurrency(totalBalanceOwed, debtsCurrencyCode)"
                label="Total Amount to be repaid"
                :badge-text="`${page.props.items.length} item(s)`"
            />

            <StatCard
                :icon="Banknote"
                :value="formatCurrency(nextPaymentTotal, debtsCurrencyCode)"
                label="Next Repayment Total"
                :badge-text="`${itemsAwaitingPayment.length} item(s)`"
            />

            <StatCard
                :icon="BanknoteArrowDown"
                :value="formatCurrency(totalMonthlyInterest, debtsCurrencyCode)"
                label="Monthly Interest"
                :badge-text="`${page.props.items.length} item(s)`"
            />

            <StatCard
                :icon="BanknoteArrowDown"
                :value="formatCurrency(totalInterest, debtsCurrencyCode)"
                label="Total Interest"
                :badge-text="`${page.props.items.length} item(s)`"
            />
        </div>

        <div v-if="page.props.items.length" class="box mt-6 p-6">
            <h2 class="text-lg font-medium">Balance by category</h2>
            <div class="mt-4">
                <CategoryBarChart
                    :labels="balanceByCategory.map((c) => c.label)"
                    :amounts="balanceByCategory.map((c) => c.amount)"
                    :currency-code="debtsCurrencyCode"
                />
            </div>
        </div>
    </div>

    <Dialog v-model:open="dialogOpen">
        <DialogScrollContent>
            <Form
                :key="editingItem?.id ?? 'create'"
                v-bind="formAction"
                @success="dialogOpen = false"
                v-slot="{ errors, processing }"
                class="space-y-4"
            >
                <DialogHeader>
                    <DialogTitle>{{
                        editingItem ? 'Edit Debt' : 'Add Debt'
                    }}</DialogTitle>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        name="name"
                        :default-value="editingItem?.name"
                        required
                    />
                    <p v-if="errors.name" class="text-xs text-danger">
                        {{ errors.name }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="category">Category</Label>
                    <select
                        id="category"
                        name="category"
                        class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                        required
                    >
                        <option
                            v-for="option in page.props.categoryOptions"
                            :key="option.value"
                            :value="option.value"
                            :selected="option.value === editingItem?.category"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <p v-if="errors.category" class="text-xs text-danger">
                        {{ errors.category }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="amount_borrowed">
                            Amount Borrowed (without interest)
                        </Label>
                        <Input
                            id="amount_borrowed"
                            name="amount_borrowed"
                            type="number"
                            step="0.01"
                            min="0"
                            :default-value="editingItem?.amountBorrowed"
                            required
                        />
                        <p
                            v-if="errors.amount_borrowed"
                            class="text-xs text-danger"
                        >
                            {{ errors.amount_borrowed }}
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="total_repayment_amount">
                            Total Repayment Amount
                        </Label>
                        <Input
                            id="total_repayment_amount"
                            name="total_repayment_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            :default-value="editingItem?.totalRepaymentAmount"
                            required
                        />
                        <p
                            v-if="errors.total_repayment_amount"
                            class="text-xs text-danger"
                        >
                            {{ errors.total_repayment_amount }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="monthly_repayment_amount">
                            Monthly Repayment Amount
                        </Label>
                        <Input
                            id="monthly_repayment_amount"
                            name="monthly_repayment_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            :default-value="editingItem?.monthlyRepaymentAmount"
                            required
                        />
                        <p
                            v-if="errors.monthly_repayment_amount"
                            class="text-xs text-danger"
                        >
                            {{ errors.monthly_repayment_amount }}
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="tenure_months">Tenure (months)</Label>
                        <Input
                            id="tenure_months"
                            name="tenure_months"
                            type="number"
                            step="1"
                            min="1"
                            :default-value="editingItem?.tenureMonths"
                            required
                        />
                        <p
                            v-if="errors.tenure_months"
                            class="text-xs text-danger"
                        >
                            {{ errors.tenure_months }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="payments_made">
                        Number of Payments Made (optional)
                    </Label>
                    <Input
                        id="payments_made"
                        name="payments_made"
                        type="number"
                        step="1"
                        min="0"
                        :default-value="editingItem?.paymentsMade"
                    />
                    <p v-if="errors.payments_made" class="text-xs text-danger">
                        {{ errors.payments_made }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label>Schedule</Label>
                    <ScheduleField
                        v-model="schedule"
                        name="schedule"
                        :errors="errors"
                        :recurrence-options="page.props.recurrenceOptions"
                        required
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="notes">Notes</Label>
                    <Textarea
                        id="notes"
                        name="notes"
                        rows="2"
                        :default-value="editingItem?.notes ?? undefined"
                    />
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="processing">{{
                        editingItem ? 'Save' : 'Add'
                    }}</Button>
                </DialogFooter>
            </Form>
        </DialogScrollContent>
    </Dialog>
</template>
