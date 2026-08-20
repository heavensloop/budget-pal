<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { ListChecks, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import NeedsController from '@/actions/App/Http/Controllers/Web/NeedsController';
import CategoryBarChart from '@/components/CategoryBarChart.vue';
import ScheduleField from '@/components/ScheduleField.vue';
import type {
    RecurrenceOption,
    ScheduleValue,
} from '@/components/ScheduleField.vue';
import SearchableComboBox from '@/components/SearchableComboBox.vue';
import type { SearchableComboBoxOption } from '@/components/SearchableComboBox.vue';
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
import NeedsTable from './NeedsTable.vue';
import type { NeedItem, SortColumn } from './NeedsTable.vue';

type Category = { id: number; name: string };

const page = usePage<{
    categories: Category[];
    items: NeedItem[];
    sort: SortColumn;
    direction: 'asc' | 'desc';
    showArchived: boolean;
    recurrenceOptions: RecurrenceOption[];
}>();

const { formatCurrency } = useCurrency();

const totalNeedsAmount = computed(() =>
    page.props.items.reduce((sum, item) => sum + item.amount, 0),
);

const needsCurrencyCode = computed(
    () => page.props.items[0]?.currencyCode ?? 'NGN',
);

const needsByCategory = computed(() => {
    const totals = new Map<string, number>();

    for (const item of page.props.items) {
        totals.set(
            item.category,
            (totals.get(item.category) ?? 0) + item.amount,
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
const editingItem = ref<NeedItem | null>(null);
const schedule = ref<ScheduleValue>(null);
const selectedCategory = ref<SearchableComboBoxOption | null>(null);

const categoryOptions = computed(() =>
    page.props.categories.map((category) => ({
        value: category.id,
        label: category.name,
    })),
);

// Needs always repeat, so we default to Monthly starting today - the
// user can change the date or switch recurrence, but "does not repeat"
// isn't an option for this domain.
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
    selectedCategory.value = null;
    dialogOpen.value = true;
}

function openEditDialog(item: NeedItem) {
    editingItem.value = item;
    schedule.value = item.schedule ?? defaultSchedule();
    selectedCategory.value =
        categoryOptions.value.find(
            (option) => option.value === item.categoryId,
        ) ?? null;
    dialogOpen.value = true;
}

const formAction = computed(() =>
    editingItem.value
        ? NeedsController.update.form(editingItem.value.id)
        : NeedsController.store.form(),
);

function archiveItem(item: NeedItem) {
    router.patch(
        NeedsController.updateStatus(item.id).url,
        { status: 'archived' },
        { preserveScroll: true },
    );
}

function restoreItem(item: NeedItem) {
    router.patch(
        NeedsController.updateStatus(item.id).url,
        { status: 'pending' },
        { preserveScroll: true },
    );
}

function destroy(item: NeedItem) {
    if (!confirm(`Delete "${item.name}"?`)) {
        return;
    }

    router.delete(NeedsController.destroy(item.id).url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Needs" />

    <div class="mt-8 flex items-center justify-between">
        <h1 class="text-2xl font-medium">Needs</h1>
        <div class="flex items-center gap-2">
            <Button variant="outline" @click="toggleShowArchived">
                {{
                    page.props.showArchived ? 'Hide Archived' : 'Show Archived'
                }}
            </Button>
            <Button @click="openCreateDialog">
                <Plus class="size-4" />
                Add Need
            </Button>
        </div>
    </div>

    <div class="mt-6 mb-10 grid grid-cols-12 gap-6">
        <div
            class="col-span-12"
            :class="page.props.items.length ? 'lg:col-span-8' : ''"
        >
            <NeedsTable
                :items="page.props.items"
                :sort="page.props.sort"
                :direction="page.props.direction"
                @sort="sortBy"
                @edit="openEditDialog"
                @archive="archiveItem"
                @restore="restoreItem"
                @destroy="destroy"
            />
        </div>

        <div
            v-if="page.props.items.length"
            class="col-span-12 flex flex-col gap-6 lg:col-span-4"
        >
            <StatCard
                :icon="ListChecks"
                :value="formatCurrency(totalNeedsAmount, needsCurrencyCode)"
                label="Total Needs"
                :badge-text="`${page.props.items.length} item(s)`"
            />

            <div class="box p-6">
                <h2 class="text-lg font-medium">Spend by category</h2>
                <div class="mt-4">
                    <CategoryBarChart
                        :labels="needsByCategory.map((c) => c.label)"
                        :amounts="needsByCategory.map((c) => c.amount)"
                        :currency-code="needsCurrencyCode"
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
                        editingItem ? 'Edit Need' : 'Add Need'
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
                    <Label for="category_id">Category</Label>
                    <SearchableComboBox
                        id="category_id"
                        v-model="selectedCategory"
                        name="category_id"
                        :options="categoryOptions"
                        placeholder="Search categories..."
                    />
                    <p v-if="errors.category_id" class="text-xs text-danger">
                        {{ errors.category_id }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="amount">Amount</Label>
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
