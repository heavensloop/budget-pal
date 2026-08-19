<script setup lang="ts">
import {
    Archive,
    ArchiveRestore,
    ArrowDown,
    ArrowUp,
    ChevronDown,
    Pencil,
    Trash2,
} from '@lucide/vue';
import { reactive } from 'vue';
import type { ScheduleValue } from '@/components/ScheduleField.vue';
import { useCurrency } from '@/composables/useCurrency';

export type NeedItem = {
    id: number;
    categoryId: number;
    category: string;
    name: string;
    amount: number;
    currencyCode: string;
    status: 'pending' | 'done' | 'skipped' | 'archived';
    schedule: ScheduleValue;
    nextPaymentDate: string | null;
    notes: string | null;
};

export type SortColumn = 'name' | 'category' | 'amount';

const props = defineProps<{
    items: NeedItem[];
    sort: SortColumn;
    direction: 'asc' | 'desc';
}>();

const emit = defineEmits<{
    sort: [column: SortColumn];
    edit: [item: NeedItem];
    archive: [item: NeedItem];
    restore: [item: NeedItem];
    destroy: [item: NeedItem];
}>();

const { formatCurrency } = useCurrency();

const sortableColumns: { key: SortColumn; label: string }[] = [
    { key: 'name', label: 'Item' },
    { key: 'category', label: 'Category' },
    { key: 'amount', label: 'Amount' },
];

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

const expandedIds = reactive(new Set<number>());

function toggleExpanded(item: NeedItem) {
    if (expandedIds.has(item.id)) {
        expandedIds.delete(item.id);
    } else {
        expandedIds.add(item.id);
    }
}

function onArchiveOrRestore(item: NeedItem) {
    if (item.status === 'archived') {
        emit('restore', item);
    } else {
        emit('archive', item);
    }
}
</script>

<template>
    <div v-if="!items.length" class="box py-12 text-center text-sm opacity-50">
        No needs yet
    </div>
    <div v-else>
        <!-- Table: md and up -->
        <div class="box hidden overflow-x-auto p-2 md:block">
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
                                    props.sort === column.key
                                        ? 'opacity-100'
                                        : 'opacity-70'
                                "
                                @click="emit('sort', column.key)"
                            >
                                {{ column.label }}
                                <ArrowUp
                                    v-if="
                                        props.sort === column.key &&
                                        props.direction === 'asc'
                                    "
                                    class="size-3"
                                />
                                <ArrowDown
                                    v-else-if="
                                        props.sort === column.key &&
                                        props.direction === 'desc'
                                    "
                                    class="size-3"
                                />
                            </button>
                        </th>
                        <th class="p-3 font-medium">Next payment date</th>
                        <th class="p-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="item in items"
                        :key="item.id"
                        class="border-b border-foreground/5 last:border-0"
                    >
                        <td class="p-3">
                            {{ item.name }}
                            <span
                                v-if="item.status === 'archived'"
                                class="ml-2 rounded-full bg-foreground/10 px-2 py-0.5 text-xs font-medium opacity-60"
                            >
                                Archived
                            </span>
                        </td>
                        <td class="p-3 opacity-70">{{ item.category }}</td>
                        <td class="p-3">
                            {{ formatCurrency(item.amount, item.currencyCode) }}
                        </td>
                        <td class="p-3">
                            <span v-if="item.nextPaymentDate" class="text-sm">
                                {{ formatDate(item.nextPaymentDate) }}
                            </span>
                            <span v-else class="text-sm opacity-50">
                                Not scheduled
                            </span>
                        </td>
                        <td class="p-3">
                            <div class="flex justify-end gap-1">
                                <button
                                    type="button"
                                    class="rounded-lg p-1.5 hover:bg-foreground/5"
                                    @click="emit('edit', item)"
                                >
                                    <Pencil class="size-4 opacity-60" />
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg p-1.5 hover:bg-foreground/5"
                                    :title="
                                        item.status === 'archived'
                                            ? 'Restore'
                                            : 'Archive'
                                    "
                                    @click="onArchiveOrRestore(item)"
                                >
                                    <ArchiveRestore
                                        v-if="item.status === 'archived'"
                                        class="size-4 opacity-60"
                                    />
                                    <Archive v-else class="size-4 opacity-60" />
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg p-1.5 hover:bg-foreground/5"
                                    @click="emit('destroy', item)"
                                >
                                    <Trash2 class="size-4 text-danger" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Cards: below md -->
        <div class="flex flex-col gap-2 md:hidden" data-testid="need-cards">
            <div
                v-for="item in items"
                :key="item.id"
                class="box cursor-pointer p-3"
                data-testid="need-card"
                @click="toggleExpanded(item)"
            >
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="truncate font-medium">{{
                                item.name
                            }}</span>
                            <span
                                v-if="item.status === 'archived'"
                                class="shrink-0 rounded-full bg-foreground/10 px-2 py-0.5 text-xs font-medium opacity-60"
                            >
                                Archived
                            </span>
                        </div>
                        <div class="mt-0.5 text-sm opacity-70">
                            {{ item.category }}
                        </div>
                    </div>
                    <div class="flex flex-none items-center gap-2">
                        <span class="font-medium">{{
                            formatCurrency(item.amount, item.currencyCode)
                        }}</span>
                        <ChevronDown
                            class="size-4 opacity-50 transition-transform"
                            :class="{ 'rotate-180': expandedIds.has(item.id) }"
                        />
                    </div>
                </div>

                <div
                    v-if="expandedIds.has(item.id)"
                    class="mt-3 flex items-center justify-between gap-2 border-t border-foreground/10 pt-3"
                    @click.stop
                >
                    <span
                        v-if="item.nextPaymentDate"
                        class="text-sm opacity-70"
                    >
                        {{ formatDate(item.nextPaymentDate) }}
                    </span>
                    <span v-else class="text-sm opacity-50">
                        Not scheduled
                    </span>
                    <div class="flex gap-1">
                        <button
                            type="button"
                            class="rounded-lg p-1.5 hover:bg-foreground/5"
                            @click="emit('edit', item)"
                        >
                            <Pencil class="size-4 opacity-60" />
                        </button>
                        <button
                            type="button"
                            class="rounded-lg p-1.5 hover:bg-foreground/5"
                            :title="
                                item.status === 'archived'
                                    ? 'Restore'
                                    : 'Archive'
                            "
                            @click="onArchiveOrRestore(item)"
                        >
                            <ArchiveRestore
                                v-if="item.status === 'archived'"
                                class="size-4 opacity-60"
                            />
                            <Archive v-else class="size-4 opacity-60" />
                        </button>
                        <button
                            type="button"
                            class="rounded-lg p-1.5 hover:bg-foreground/5"
                            @click="emit('destroy', item)"
                        >
                            <Trash2 class="size-4 text-danger" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
