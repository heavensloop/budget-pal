<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { Banknote, CreditCard, Plus } from '@lucide/vue';
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
    itemsAwaitingPayment.value.reduce((sum, item) => sum + item.amount, 0),
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

    <div class="mt-6 mb-10 grid grid-cols-12 gap-6">
        <div
            class="col-span-12"
            :class="page.props.items.length ? 'lg:col-span-8' : ''"
        >
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
        </div>

        <div
            v-if="page.props.items.length"
            class="col-span-12 flex flex-col gap-6 lg:col-span-4"
        >
            <StatCard
                :icon="CreditCard"
                :value="formatCurrency(totalBalanceOwed, debtsCurrencyCode)"
                label="Total Balance Owed"
                :badge-text="`${page.props.items.length} item(s)`"
            />

            <StatCard
                :icon="Banknote"
                :value="formatCurrency(nextPaymentTotal, debtsCurrencyCode)"
                label="Next Repayment Total"
                :badge-text="`${itemsAwaitingPayment.length} item(s)`"
            />

            <div class="box p-6">
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
                        <Label for="principal">Principal</Label>
                        <Input
                            id="principal"
                            name="principal"
                            type="number"
                            step="0.01"
                            min="0"
                            :default-value="editingItem?.principal"
                            required
                        />
                        <p v-if="errors.principal" class="text-xs text-danger">
                            {{ errors.principal }}
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="balance">Current balance</Label>
                        <Input
                            id="balance"
                            name="balance"
                            type="number"
                            step="0.01"
                            min="0"
                            :default-value="editingItem?.balance"
                        />
                        <p v-if="errors.balance" class="text-xs text-danger">
                            {{ errors.balance }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="amount">Repayment Amount</Label>
                    <Input
                        id="amount"
                        name="amount"
                        type="number"
                        step="0.01"
                        min="0"
                        :default-value="editingItem?.amount"
                        required
                    />
                    <p v-if="errors.amount" class="text-xs text-danger">
                        {{ errors.amount }}
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
