<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { ListTodo, Plus, ShoppingBag, Wallet } from '@lucide/vue';
import { computed, ref } from 'vue';
import WantsController from '@/actions/App/Http/Controllers/Web/WantsController';
import CategoryBarChart from '@/components/CategoryBarChart.vue';
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
import WantsTable from './WantsTable.vue';
import type { SortColumn, WantItem } from './WantsTable.vue';

type Option = { value: string; label: string };

const page = usePage<{
    categoryOptions: Option[];
    statusOptions: Option[];
    items: WantItem[];
    sort: SortColumn;
    direction: 'asc' | 'desc';
    showArchived: boolean;
}>();

const { formatCurrency } = useCurrency();

const plannedItems = computed(() =>
    page.props.items.filter((item) => item.status === 'planned'),
);

const totalPlanned = computed(() =>
    plannedItems.value.reduce((sum, item) => sum + item.amount, 0),
);

const purchasedThisMonth = computed(() => {
    const now = new Date();

    return page.props.items
        .filter((item) => {
            if (!item.purchasedAt) {
                return false;
            }

            const purchased = new Date(`${item.purchasedAt}T00:00:00`);

            return (
                purchased.getFullYear() === now.getFullYear() &&
                purchased.getMonth() === now.getMonth()
            );
        })
        .reduce((sum, item) => sum + item.amount, 0);
});

const wantsCurrencyCode = computed(
    () => page.props.items[0]?.currencyCode ?? 'NGN',
);

const plannedByCategory = computed(() => {
    const totals = new Map<string, number>();

    for (const item of plannedItems.value) {
        totals.set(
            item.categoryLabel,
            (totals.get(item.categoryLabel) ?? 0) + item.amount,
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
const editingItem = ref<WantItem | null>(null);

function openCreateDialog() {
    editingItem.value = null;
    dialogOpen.value = true;
}

function openEditDialog(item: WantItem) {
    editingItem.value = item;
    dialogOpen.value = true;
}

const formAction = computed(() =>
    editingItem.value
        ? WantsController.update.form(editingItem.value.id)
        : WantsController.store.form(),
);

function archiveItem(item: WantItem) {
    router.patch(
        WantsController.updateStatus(item.id).url,
        { status: 'archived' },
        { preserveScroll: true },
    );
}

function restoreItem(item: WantItem) {
    router.patch(
        WantsController.updateStatus(item.id).url,
        { status: 'planned' },
        { preserveScroll: true },
    );
}

function markPurchased(item: WantItem) {
    router.patch(
        WantsController.updateStatus(item.id).url,
        { status: 'purchased' },
        { preserveScroll: true },
    );
}

function reorder(item: WantItem, direction: 'up' | 'down') {
    router.patch(
        WantsController.reorder(item.id).url,
        { direction },
        { preserveScroll: true },
    );
}

function destroy(item: WantItem) {
    if (!confirm(`Delete "${item.name}"?`)) {
        return;
    }

    router.delete(WantsController.destroy(item.id).url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Wants" />

    <div class="mt-8 flex items-center justify-between">
        <h1 class="text-2xl font-medium">Wants</h1>
        <div class="flex items-center gap-2">
            <Button variant="outline" @click="toggleShowArchived">
                {{
                    page.props.showArchived ? 'Hide Archived' : 'Show Archived'
                }}
            </Button>
            <Button @click="openCreateDialog">
                <Plus class="size-4" />
                Add Want
            </Button>
        </div>
    </div>

    <div class="mt-6 mb-10">
        <WantsTable
            :items="page.props.items"
            :sort="page.props.sort"
            :direction="page.props.direction"
            @sort="sortBy"
            @edit="openEditDialog"
            @archive="archiveItem"
            @restore="restoreItem"
            @destroy="destroy"
            @mark-purchased="markPurchased"
            @reorder="reorder"
        />

        <div
            v-if="page.props.items.length"
            class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
        >
            <StatCard
                :icon="ShoppingBag"
                :value="formatCurrency(totalPlanned, wantsCurrencyCode)"
                label="Total Planned"
                :badge-text="`${plannedItems.length} item(s)`"
            />

            <StatCard
                :icon="Wallet"
                :value="formatCurrency(purchasedThisMonth, wantsCurrencyCode)"
                label="Purchased This Month"
                :badge-text="`${page.props.items.length} item(s)`"
            />

            <StatCard
                :icon="ListTodo"
                :value="String(plannedItems.length)"
                label="Items Planned"
                :badge-text="`${page.props.items.length} item(s)`"
            />
        </div>

        <div v-if="plannedItems.length" class="box mt-6 p-6">
            <h2 class="text-lg font-medium">Planned by category</h2>
            <div class="mt-4">
                <CategoryBarChart
                    :labels="plannedByCategory.map((c) => c.label)"
                    :amounts="plannedByCategory.map((c) => c.amount)"
                    :currency-code="wantsCurrencyCode"
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
                        editingItem ? 'Edit Want' : 'Add Want'
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
