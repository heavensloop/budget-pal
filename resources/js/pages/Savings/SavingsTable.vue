<script setup lang="ts">
import {
    Archive,
    ArchiveRestore,
    Banknote,
    ChevronDown,
    EllipsisVertical,
    Pencil,
    Trash2,
} from '@lucide/vue';
import { reactive } from 'vue';
import type { ScheduleValue } from '@/components/ScheduleField.vue';
import SortButton from '@/components/SortButton.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useCurrency } from '@/composables/useCurrency';

export type SavingsStatus = 'pending' | 'ongoing' | 'archived' | 'completed';

export type SavingsItem = {
    id: number;
    type: string;
    typeLabel: string;
    name: string;
    targetAmount: number;
    installmentAmount: number;
    installmentsMade: number;
    targetProfit: number | null;
    maturityDate: string | null;
    amountSaved: number;
    remainingToTarget: number;
    profitEarned: number;
    currencyCode: string;
    status: SavingsStatus;
    statusLabel: string;
    lastContributionDate: string | null;
    schedule: ScheduleValue;
    nextContributionDate: string | null;
    canRecordContribution: boolean;
    notes: string | null;
};

export type SortColumn = 'name' | 'type' | 'amount' | 'saved';

const props = defineProps<{
    items: SavingsItem[];
    sort: SortColumn;
    direction: 'asc' | 'desc';
}>();

const emit = defineEmits<{
    sort: [column: SortColumn];
    edit: [item: SavingsItem];
    archive: [item: SavingsItem];
    restore: [item: SavingsItem];
    destroy: [item: SavingsItem];
    recordContribution: [item: SavingsItem];
}>();

const { formatCurrency } = useCurrency();

const STATUS_STYLES: Record<SavingsStatus, string> = {
    pending: 'bg-foreground/10 text-foreground/70',
    ongoing: 'bg-primary/10 text-primary',
    completed: 'bg-success/15 text-success',
    archived: 'bg-foreground/10 text-foreground/50',
};

function formatMonthYear(dateString: string): string {
    return new Date(`${dateString}T00:00:00`).toLocaleDateString('en-US', {
        month: 'short',
        year: 'numeric',
    });
}

// Savings items without a schedule are assumed to repeat monthly by
// default; we just don't have an anchor date to compute a real next
// contribution date from, so fall back to assuming it's due sometime next
// month.
function nextContributionLabel(item: SavingsItem): string {
    if (item.nextContributionDate) {
        return formatMonthYear(item.nextContributionDate);
    }

    const today = new Date();
    const nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);

    return nextMonth.toLocaleDateString('en-US', {
        month: 'short',
        year: 'numeric',
    });
}

const expandedIds = reactive(new Set<number>());

function toggleExpanded(item: SavingsItem) {
    if (expandedIds.has(item.id)) {
        expandedIds.delete(item.id);
    } else {
        expandedIds.add(item.id);
    }
}

function onArchiveOrRestore(item: SavingsItem) {
    if (item.status === 'archived') {
        emit('restore', item);
    } else {
        emit('archive', item);
    }
}
</script>

<template>
    <div v-if="!items.length" class="box py-12 text-center text-sm opacity-50">
        No savings or investments yet
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
                        <th class="p-3 text-right font-medium">
                            Target Amount
                        </th>
                        <th class="p-3 text-right font-medium">
                            <SortButton
                                label="Installment Amount"
                                :active="props.sort === 'amount'"
                                :direction="props.direction"
                                align-end
                                @click="emit('sort', 'amount')"
                            />
                        </th>
                        <th class="p-3 font-medium">
                            <SortButton
                                label="Type"
                                :active="props.sort === 'type'"
                                :direction="props.direction"
                                @click="emit('sort', 'type')"
                            />
                        </th>
                        <th class="p-3 text-right font-medium">
                            Target Profit
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
                        <td class="w-1/6 p-3">
                            {{ item.name }}
                        </td>
                        <td class="p-3 text-right">
                            <div>
                                {{
                                    formatCurrency(
                                        item.targetAmount,
                                        item.currencyCode,
                                    )
                                }}
                            </div>
                            <div class="mt-1 flex justify-end">
                                <span
                                    class="inline-block rounded-full bg-foreground/10 px-2 py-0.5 text-xs font-normal opacity-70"
                                >
                                    {{
                                        formatCurrency(
                                            item.amountSaved,
                                            item.currencyCode,
                                        )
                                    }}
                                    saved
                                </span>
                            </div>
                        </td>
                        <td class="p-3 text-right">
                            <div>
                                {{
                                    formatCurrency(
                                        item.installmentAmount,
                                        item.currencyCode,
                                    )
                                }}
                            </div>
                            <div class="mt-1 flex justify-end">
                                <span
                                    class="inline-block rounded-full bg-primary/10 px-2 py-0.5 text-xs font-normal text-primary"
                                >
                                    {{ nextContributionLabel(item) }}
                                </span>
                            </div>
                        </td>
                        <td class="p-3 opacity-70">{{ item.typeLabel }}</td>
                        <td class="p-3 text-right opacity-70">
                            <template v-if="item.targetProfit !== null">
                                <div>
                                    {{
                                        formatCurrency(
                                            item.targetProfit,
                                            item.currencyCode,
                                        )
                                    }}
                                </div>
                                <div class="mt-1 flex justify-end">
                                    <span
                                        class="inline-block rounded-full bg-foreground/10 px-2 py-0.5 text-xs font-normal opacity-70"
                                    >
                                        {{
                                            formatCurrency(
                                                item.profitEarned,
                                                item.currencyCode,
                                            )
                                        }}
                                        earned
                                    </span>
                                </div>
                            </template>
                            <template v-else>&mdash;</template>
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
                            <div class="flex justify-end">
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
                                                !item.canRecordContribution
                                            "
                                            @click="
                                                item.canRecordContribution &&
                                                emit('recordContribution', item)
                                            "
                                        >
                                            <Banknote class="size-4" />
                                            Record Contribution
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
        <div class="flex flex-col gap-2 md:hidden" data-testid="savings-cards">
            <div
                v-for="item in items"
                :key="item.id"
                class="box cursor-pointer p-3"
                data-testid="savings-card"
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
                            {{ item.typeLabel }}
                        </div>
                    </div>
                    <div class="flex flex-none items-center gap-2">
                        <span class="font-medium">{{
                            formatCurrency(item.amountSaved, item.currencyCode)
                        }}</span>
                        <ChevronDown
                            class="size-4 opacity-50 transition-transform"
                            :class="{ 'rotate-180': expandedIds.has(item.id) }"
                        />
                    </div>
                </div>

                <div
                    v-if="expandedIds.has(item.id)"
                    class="mt-3 border-t border-foreground/10 pt-3"
                    @click.stop
                >
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="opacity-70">Next contribution</span>
                            <span class="font-medium">{{
                                nextContributionLabel(item)
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="opacity-70">
                                Next contribution amount
                            </span>
                            <span class="font-medium">
                                {{
                                    formatCurrency(
                                        item.installmentAmount,
                                        item.currencyCode,
                                    )
                                }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="mt-3 flex justify-end gap-2 border-t border-foreground/10 pt-3"
                    >
                        <button
                            type="button"
                            class="cursor-pointer rounded-lg p-3 hover:bg-foreground/5 disabled:cursor-not-allowed disabled:opacity-30"
                            title="Record Contribution"
                            :disabled="!item.canRecordContribution"
                            @click="emit('recordContribution', item)"
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
                                item.status === 'archived'
                                    ? 'Restore'
                                    : 'Archive'
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
    </div>
</template>
