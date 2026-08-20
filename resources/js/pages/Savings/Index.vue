<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { Banknote, CalendarClock, PiggyBank, Plus, Target } from '@lucide/vue';
import { computed, ref } from 'vue';
import SavingsController from '@/actions/App/Http/Controllers/Web/SavingsController';
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
import SavingsTable from './SavingsTable.vue';
import type { SavingsItem, SortColumn } from './SavingsTable.vue';

type Option = { value: string; label: string };

const page = usePage<{
    typeOptions: Option[];
    statusOptions: Option[];
    items: SavingsItem[];
    sort: SortColumn;
    direction: 'asc' | 'desc';
    showArchived: boolean;
    recurrenceOptions: RecurrenceOption[];
}>();

const { formatCurrency } = useCurrency();

const totalAmountSaved = computed(() =>
    page.props.items.reduce((sum, item) => sum + item.amountSaved, 0),
);

const totalRemainingToTarget = computed(() =>
    page.props.items.reduce((sum, item) => sum + item.remainingToTarget, 0),
);

const totalProfitEarned = computed(() =>
    page.props.items.reduce((sum, item) => sum + item.profitEarned, 0),
);

const nextMaturityLabel = computed(() => {
    const dates = page.props.items
        .map((item) => item.maturityDate)
        .filter((date): date is string => date !== null)
        .sort();

    if (!dates.length) {
        return '—';
    }

    return new Date(`${dates[0]}T00:00:00`).toLocaleDateString('en-US', {
        month: 'short',
        year: 'numeric',
    });
});

const savingsCurrencyCode = computed(
    () => page.props.items[0]?.currencyCode ?? 'NGN',
);

const savedByType = computed(() => {
    const totals = new Map<string, number>();

    for (const item of page.props.items) {
        totals.set(
            item.typeLabel,
            (totals.get(item.typeLabel) ?? 0) + item.amountSaved,
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
const editingItem = ref<SavingsItem | null>(null);
const schedule = ref<ScheduleValue>(null);

// Savings goals always repeat, so we default to Monthly starting today -
// the user can change the date/recurrence, but "does not repeat" isn't an
// option for this domain.
function defaultSchedule(): ScheduleValue {
    return {
        recurrence: 'monthly',
        startDate: new Date().toISOString().slice(0, 10),
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

function openEditDialog(item: SavingsItem) {
    editingItem.value = item;
    schedule.value = item.schedule ?? defaultSchedule();
    dialogOpen.value = true;
}

const formAction = computed(() =>
    editingItem.value
        ? SavingsController.update.form(editingItem.value.id)
        : SavingsController.store.form(),
);

function archiveItem(item: SavingsItem) {
    router.patch(
        SavingsController.updateStatus(item.id).url,
        { status: 'archived' },
        { preserveScroll: true },
    );
}

function restoreItem(item: SavingsItem) {
    router.patch(
        SavingsController.updateStatus(item.id).url,
        { status: 'pending' },
        { preserveScroll: true },
    );
}

function recordContribution(item: SavingsItem) {
    router.patch(
        SavingsController.recordContribution(item.id).url,
        {},
        { preserveScroll: true },
    );
}

function destroy(item: SavingsItem) {
    if (!confirm(`Delete "${item.name}"?`)) {
        return;
    }

    router.delete(SavingsController.destroy(item.id).url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Savings & Investments" />

    <div class="mt-8 flex items-center justify-between">
        <h1 class="text-2xl font-medium">Savings & Investments</h1>
        <div class="flex items-center gap-2">
            <Button variant="outline" @click="toggleShowArchived">
                {{
                    page.props.showArchived ? 'Hide Archived' : 'Show Archived'
                }}
            </Button>
            <Button @click="openCreateDialog">
                <Plus class="size-4" />
                Add Goal
            </Button>
        </div>
    </div>

    <div class="mt-6 mb-10">
        <SavingsTable
            :items="page.props.items"
            :sort="page.props.sort"
            :direction="page.props.direction"
            @sort="sortBy"
            @edit="openEditDialog"
            @archive="archiveItem"
            @restore="restoreItem"
            @destroy="destroy"
            @record-contribution="recordContribution"
        />

        <div
            v-if="page.props.items.length"
            class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4"
        >
            <StatCard
                :icon="PiggyBank"
                :value="formatCurrency(totalAmountSaved, savingsCurrencyCode)"
                label="Total Amount Saved"
                :badge-text="`${page.props.items.length} item(s)`"
            />

            <StatCard
                :icon="Target"
                :value="
                    formatCurrency(totalRemainingToTarget, savingsCurrencyCode)
                "
                label="Remaining to Target"
                :badge-text="`${page.props.items.length} item(s)`"
            />

            <StatCard
                :icon="Banknote"
                :value="formatCurrency(totalProfitEarned, savingsCurrencyCode)"
                label="Total Profit Earned"
                :badge-text="`${page.props.items.length} item(s)`"
            />

            <StatCard
                :icon="CalendarClock"
                :value="nextMaturityLabel"
                label="Next Maturity"
                :badge-text="`${page.props.items.length} item(s)`"
            />
        </div>

        <div v-if="page.props.items.length" class="box mt-6 p-6">
            <h2 class="text-lg font-medium">Saved by type</h2>
            <div class="mt-4">
                <CategoryBarChart
                    :labels="savedByType.map((c) => c.label)"
                    :amounts="savedByType.map((c) => c.amount)"
                    :currency-code="savingsCurrencyCode"
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
                        editingItem ? 'Edit Goal' : 'Add Goal'
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
                    <Label for="type">Type</Label>
                    <select
                        id="type"
                        name="type"
                        class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                        required
                    >
                        <option
                            v-for="option in page.props.typeOptions"
                            :key="option.value"
                            :value="option.value"
                            :selected="option.value === editingItem?.type"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <p v-if="errors.type" class="text-xs text-danger">
                        {{ errors.type }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="target_amount">Target Amount</Label>
                        <Input
                            id="target_amount"
                            name="target_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            :default-value="editingItem?.targetAmount"
                            required
                        />
                        <p
                            v-if="errors.target_amount"
                            class="text-xs text-danger"
                        >
                            {{ errors.target_amount }}
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="installment_amount">
                            Installment Amount
                        </Label>
                        <Input
                            id="installment_amount"
                            name="installment_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            :default-value="editingItem?.installmentAmount"
                            required
                        />
                        <p
                            v-if="errors.installment_amount"
                            class="text-xs text-danger"
                        >
                            {{ errors.installment_amount }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="target_profit">
                            Target Profit (optional)
                        </Label>
                        <Input
                            id="target_profit"
                            name="target_profit"
                            type="number"
                            step="0.01"
                            min="0"
                            :default-value="
                                editingItem?.targetProfit ?? undefined
                            "
                        />
                        <p
                            v-if="errors.target_profit"
                            class="text-xs text-danger"
                        >
                            {{ errors.target_profit }}
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="maturity_date">
                            Maturity Date (optional)
                        </Label>
                        <Input
                            id="maturity_date"
                            name="maturity_date"
                            type="date"
                            :default-value="
                                editingItem?.maturityDate ?? undefined
                            "
                        />
                        <p
                            v-if="errors.maturity_date"
                            class="text-xs text-danger"
                        >
                            {{ errors.maturity_date }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="status">Status</Label>
                    <select
                        id="status"
                        name="status"
                        class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                        required
                    >
                        <option
                            v-for="option in page.props.statusOptions"
                            :key="option.value"
                            :value="option.value"
                            :selected="
                                option.value ===
                                (editingItem?.status ?? 'pending')
                            "
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <p v-if="errors.status" class="text-xs text-danger">
                        {{ errors.status }}
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
