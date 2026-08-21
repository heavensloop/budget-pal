<script setup lang="ts">
import {
    Archive,
    ArchiveRestore,
    ArrowDown,
    ArrowUp,
    Banknote,
    ChevronDown,
    EllipsisVertical,
    Pencil,
    Trash2,
} from '@lucide/vue';
import { reactive } from 'vue';
import SortButton from '@/components/SortButton.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useCurrency } from '@/composables/useCurrency';

export type WantStatus = 'planned' | 'purchased' | 'archived';

export type WantItem = {
    id: number;
    name: string;
    category: string;
    categoryLabel: string;
    amount: number;
    currencyCode: string;
    status: WantStatus;
    statusLabel: string;
    position: number;
    purchasedAt: string | null;
    notes: string | null;
};

export type SortColumn = 'name' | 'category' | 'amount' | 'position';

const props = defineProps<{
    items: WantItem[];
    sort: SortColumn;
    direction: 'asc' | 'desc';
}>();

const emit = defineEmits<{
    sort: [column: SortColumn];
    edit: [item: WantItem];
    archive: [item: WantItem];
    restore: [item: WantItem];
    destroy: [item: WantItem];
    markPurchased: [item: WantItem];
    reorder: [item: WantItem, direction: 'up' | 'down'];
}>();

const { formatCurrency } = useCurrency();

const STATUS_STYLES: Record<WantStatus, string> = {
    planned: 'bg-foreground/10 text-foreground/70',
    purchased: 'bg-success/15 text-success',
    archived: 'bg-foreground/10 text-foreground/50',
};

// Reordering is priority-order manipulation, so it only makes sense to
// show while viewing in that order, and only for items still being
// prioritized (once purchased or archived, order no longer matters).
function canReorder(item: WantItem): boolean {
    return props.sort === 'position' && item.status === 'planned';
}

const expandedIds = reactive(new Set<number>());

function toggleExpanded(item: WantItem) {
    if (expandedIds.has(item.id)) {
        expandedIds.delete(item.id);
    } else {
        expandedIds.add(item.id);
    }
}

function onArchiveOrRestore(item: WantItem) {
    if (item.status === 'archived') {
        emit('restore', item);
    } else {
        emit('archive', item);
    }
}
</script>

<template>
    <div v-if="!items.length" class="box py-12 text-center text-sm opacity-50">
        No wants yet
    </div>
    <div v-else>
        <!-- Table: md and up -->
        <div class="box hidden overflow-x-auto p-2 md:block">
            <table class="w-full text-left">
                <thead>
                    <tr
                        class="border-b border-foreground/10 text-xs uppercase opacity-60"
                    >
                        <th class="w-1/6 p-3 font-medium">
                            <SortButton
                                label="Item"
                                :active="props.sort === 'name'"
                                :direction="props.direction"
                                @click="emit('sort', 'name')"
                            />
                        </th>
                        <th class="p-3 font-medium">
                            <SortButton
                                label="Category"
                                :active="props.sort === 'category'"
                                :direction="props.direction"
                                @click="emit('sort', 'category')"
                            />
                        </th>
                        <th class="p-3 text-right font-medium">
                            <SortButton
                                label="Amount"
                                :active="props.sort === 'amount'"
                                :direction="props.direction"
                                align-end
                                @click="emit('sort', 'amount')"
                            />
                        </th>
                        <th class="p-3 font-medium">Status</th>
                        <th class="p-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="item in items"
                        :key="item.id"
                        class="border-b border-foreground/5 last:border-0"
                    >
                        <td class="w-1/6 p-3">{{ item.name }}</td>
                        <td class="p-3 opacity-70">{{ item.categoryLabel }}</td>
                        <td class="p-3 text-right">
                            {{ formatCurrency(item.amount, item.currencyCode) }}
                        </td>
                        <td class="p-3">
                            <span
                                class="inline-block rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="STATUS_STYLES[item.status]"
                            >
                                {{ item.statusLabel }}
                            </span>
                        </td>
                        <td class="p-3">
                            <div class="flex justify-end gap-1">
                                <template v-if="canReorder(item)">
                                    <button
                                        type="button"
                                        class="cursor-pointer rounded-lg p-1.5 hover:bg-foreground/5"
                                        title="Move up"
                                        @click="emit('reorder', item, 'up')"
                                    >
                                        <ArrowUp class="size-4 opacity-60" />
                                    </button>
                                    <button
                                        type="button"
                                        class="cursor-pointer rounded-lg p-1.5 hover:bg-foreground/5"
                                        title="Move down"
                                        @click="emit('reorder', item, 'down')"
                                    >
                                        <ArrowDown class="size-4 opacity-60" />
                                    </button>
                                </template>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <button
                                            type="button"
                                            class="cursor-pointer rounded-lg p-1.5 hover:bg-foreground/5"
                                            title="Actions"
                                        >
                                            <EllipsisVertical
                                                class="size-4 opacity-60"
                                            />
                                        </button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent
                                        align="end"
                                        class="flex flex-col gap-1 p-2"
                                    >
                                        <DropdownMenuItem
                                            :disabled="
                                                item.status !== 'planned'
                                            "
                                            @click="
                                                item.status === 'planned' &&
                                                emit('markPurchased', item)
                                            "
                                        >
                                            <Banknote class="size-4" />
                                            Mark Purchased
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="emit('edit', item)"
                                        >
                                            <Pencil class="size-4" />
                                            Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="onArchiveOrRestore(item)"
                                        >
                                            <ArchiveRestore
                                                v-if="
                                                    item.status === 'archived'
                                                "
                                                class="size-4"
                                            />
                                            <Archive v-else class="size-4" />
                                            {{
                                                item.status === 'archived'
                                                    ? 'Restore'
                                                    : 'Archive'
                                            }}
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            variant="destructive"
                                            @click="emit('destroy', item)"
                                        >
                                            <Trash2 class="size-4" />
                                            Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Cards: below md -->
        <div class="flex flex-col gap-2 md:hidden" data-testid="wants-cards">
            <div
                v-for="item in items"
                :key="item.id"
                class="box cursor-pointer p-3"
                data-testid="want-card"
                @click="toggleExpanded(item)"
            >
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="truncate font-medium">{{
                                item.name
                            }}</span>
                            <span
                                class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="STATUS_STYLES[item.status]"
                            >
                                {{ item.statusLabel }}
                            </span>
                        </div>
                        <div class="mt-0.5 text-sm opacity-70">
                            {{ item.categoryLabel }}
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
                    class="mt-3 flex justify-end gap-1 border-t border-foreground/10 pt-3"
                    @click.stop
                >
                    <template v-if="canReorder(item)">
                        <button
                            type="button"
                            class="cursor-pointer rounded-lg p-3 hover:bg-foreground/5"
                            title="Move up"
                            @click="emit('reorder', item, 'up')"
                        >
                            <ArrowUp class="size-5 opacity-60" />
                        </button>
                        <button
                            type="button"
                            class="cursor-pointer rounded-lg p-3 hover:bg-foreground/5"
                            title="Move down"
                            @click="emit('reorder', item, 'down')"
                        >
                            <ArrowDown class="size-5 opacity-60" />
                        </button>
                    </template>
                    <button
                        v-if="item.status === 'planned'"
                        type="button"
                        class="cursor-pointer rounded-lg p-3 hover:bg-foreground/5"
                        title="Mark Purchased"
                        @click="emit('markPurchased', item)"
                    >
                        <Banknote class="size-5 opacity-60" />
                    </button>
                    <button
                        type="button"
                        class="cursor-pointer rounded-lg p-3 hover:bg-foreground/5"
                        @click="emit('edit', item)"
                    >
                        <Pencil class="size-5 opacity-60" />
                    </button>
                    <button
                        type="button"
                        class="cursor-pointer rounded-lg p-3 hover:bg-foreground/5"
                        :title="
                            item.status === 'archived' ? 'Restore' : 'Archive'
                        "
                        @click="onArchiveOrRestore(item)"
                    >
                        <ArchiveRestore
                            v-if="item.status === 'archived'"
                            class="size-5 opacity-60"
                        />
                        <Archive v-else class="size-5 opacity-60" />
                    </button>
                    <button
                        type="button"
                        class="cursor-pointer rounded-lg p-3 hover:bg-foreground/5"
                        @click="emit('destroy', item)"
                    >
                        <Trash2 class="size-5 text-danger" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
