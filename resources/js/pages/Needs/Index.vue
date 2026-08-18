<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import NeedsController from '@/actions/App/Http/Controllers/Web/NeedsController';
import SearchableComboBox from '@/components/SearchableComboBox.vue';
import type { SearchableComboBoxOption } from '@/components/SearchableComboBox.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCurrency } from '@/composables/useCurrency';
import type { CurrentMonth } from '@/composables/useMonthSelector';

type Category = { id: number; name: string };

type NeedItem = {
    id: number;
    categoryId: number;
    category: string;
    name: string;
    amount: number;
    currencyCode: string;
    status: 'pending' | 'done' | 'skipped';
    isRecurring: boolean;
    dueDay: number | null;
    dateDue: string | null;
    notes: string | null;
};

type SortColumn = 'name' | 'category' | 'amount' | 'status';

const page = usePage<{
    currentMonth: CurrentMonth;
    categories: Category[];
    items: NeedItem[];
    sort: SortColumn;
    direction: 'asc' | 'desc';
}>();

const { formatCurrency } = useCurrency();

const sortableColumns: { key: SortColumn; label: string }[] = [
    { key: 'name', label: 'Item' },
    { key: 'category', label: 'Category' },
    { key: 'amount', label: 'Amount' },
    { key: 'status', label: 'Status' },
];

function sortBy(column: SortColumn) {
    const direction =
        page.props.sort === column && page.props.direction === 'asc'
            ? 'desc'
            : 'asc';

    router.get(
        window.location.pathname,
        {
            year: page.props.currentMonth.year,
            month: page.props.currentMonth.month,
            sort: column,
            direction,
        },
        { preserveState: true, preserveScroll: true },
    );
}

const dialogOpen = ref(false);
const editingItem = ref<NeedItem | null>(null);
const isRecurring = ref(false);
const selectedCategory = ref<SearchableComboBoxOption | null>(null);

const categoryOptions = computed(() =>
    page.props.categories.map((category) => ({
        value: category.id,
        label: category.name,
    })),
);

function openCreateDialog() {
    editingItem.value = null;
    isRecurring.value = false;
    selectedCategory.value = null;
    dialogOpen.value = true;
}

function openEditDialog(item: NeedItem) {
    editingItem.value = item;
    isRecurring.value = item.isRecurring;
    selectedCategory.value =
        categoryOptions.value.find(
            (option) => option.value === item.categoryId,
        ) ?? null;
    dialogOpen.value = true;
}

const formAction = computed(() =>
    editingItem.value
        ? NeedsController.update.form(editingItem.value.id)
        : NeedsController.store.form({
              query: {
                  year: page.props.currentMonth.year,
                  month: page.props.currentMonth.month,
              },
          }),
);

const statusCycle = {
    pending: 'done',
    done: 'skipped',
    skipped: 'pending',
} as const;

function cycleStatus(item: NeedItem) {
    router.patch(
        NeedsController.updateStatus(item.id).url,
        { status: statusCycle[item.status] },
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
        <div>
            <h1 class="text-2xl font-medium">Needs</h1>
            <p class="mt-1 opacity-70">{{ page.props.currentMonth.label }}</p>
        </div>
        <Button @click="openCreateDialog">
            <Plus class="size-4" />
            Add Need
        </Button>
    </div>

    <div class="mt-6 mb-10">
        <div v-if="page.props.items.length" class="box overflow-x-auto p-2">
            <table class="w-full text-left">
                <thead>
                    <tr
                        class="border-b border-foreground/10 text-xs uppercase opacity-60"
                    >
                        <th
                            v-for="column in sortableColumns"
                            :key="column.key"
                            class="p-3 font-medium"
                        >
                            <button
                                type="button"
                                class="flex items-center gap-1 uppercase hover:opacity-100"
                                :class="
                                    page.props.sort === column.key
                                        ? 'opacity-100'
                                        : 'opacity-70'
                                "
                                @click="sortBy(column.key)"
                            >
                                {{ column.label }}
                                <ArrowUp
                                    v-if="
                                        page.props.sort === column.key &&
                                        page.props.direction === 'asc'
                                    "
                                    class="size-3"
                                />
                                <ArrowDown
                                    v-else-if="
                                        page.props.sort === column.key &&
                                        page.props.direction === 'desc'
                                    "
                                    class="size-3"
                                />
                            </button>
                        </th>
                        <th class="p-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="item in page.props.items"
                        :key="item.id"
                        class="border-b border-foreground/5 last:border-0"
                    >
                        <td class="p-3">{{ item.name }}</td>
                        <td class="p-3 opacity-70">{{ item.category }}</td>
                        <td class="p-3">
                            {{ formatCurrency(item.amount, item.currencyCode) }}
                        </td>
                        <td class="p-3">
                            <button
                                type="button"
                                @click="cycleStatus(item)"
                                title="Click to change status"
                            >
                                <StatusBadge :status="item.status" />
                            </button>
                        </td>
                        <td class="p-3">
                            <div class="flex justify-end gap-1">
                                <button
                                    type="button"
                                    class="rounded-lg p-1.5 hover:bg-foreground/5"
                                    @click="openEditDialog(item)"
                                >
                                    <Pencil class="size-4 opacity-60" />
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg p-1.5 hover:bg-foreground/5"
                                    @click="destroy(item)"
                                >
                                    <Trash2 class="size-4 text-danger" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-else class="box py-12 text-center text-sm opacity-50">
            No needs yet this month
        </div>
    </div>

    <Dialog v-model:open="dialogOpen">
        <DialogContent>
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

                <label class="flex items-center gap-2 text-sm">
                    <Checkbox
                        v-model="isRecurring"
                        name="is_recurring"
                        value="1"
                    />
                    Recurring every month
                </label>

                <div v-if="isRecurring" class="grid gap-2">
                    <Label for="due_day">Due day of month</Label>
                    <Input
                        id="due_day"
                        name="due_day"
                        type="number"
                        min="1"
                        max="31"
                        :default-value="editingItem?.dueDay ?? undefined"
                    />
                </div>

                <div v-else class="grid gap-2">
                    <Label for="date_due">Due date</Label>
                    <Input
                        id="date_due"
                        name="date_due"
                        type="date"
                        :default-value="editingItem?.dateDue ?? undefined"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="notes">Notes</Label>
                    <textarea
                        id="notes"
                        name="notes"
                        rows="2"
                        :default-value="editingItem?.notes ?? undefined"
                        class="rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    ></textarea>
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="processing">{{
                        editingItem ? 'Save' : 'Add'
                    }}</Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
