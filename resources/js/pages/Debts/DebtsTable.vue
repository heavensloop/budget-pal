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

export type DebtItem = {
    id: number;
    category: string;
    categoryLabel: string;
    name: string;
    amountBorrowed: number;
    totalRepaymentAmount: number;
    monthlyRepaymentAmount: number;
    tenureMonths: number;
    paymentsMade: number;
    balance: number;
    remainingTenure: number;
    interestMonthly: number;
    interestRate: number;
    currencyCode: string;
    status: 'pending' | 'archived';
    lastPaymentDate: string | null;
    schedule: ScheduleValue;
    nextPaymentDate: string | null;
    canRecordPayment: boolean;
    notes: string | null;
};

export type SortColumn = 'name' | 'category' | 'amount' | 'balance';

const props = defineProps<{
    items: DebtItem[];
    sort: SortColumn;
    direction: 'asc' | 'desc';
}>();

const emit = defineEmits<{
    sort: [column: SortColumn];
    edit: [item: DebtItem];
    archive: [item: DebtItem];
    restore: [item: DebtItem];
    destroy: [item: DebtItem];
    recordPayment: [item: DebtItem];
}>();

const { formatCurrency } = useCurrency();

function formatMonthYear(dateString: string): string {
    return new Date(`${dateString}T00:00:00`).toLocaleDateString('en-US', {
        month: 'short',
        year: 'numeric',
    });
}

function totalInterestAmount(item: DebtItem): number {
    return item.totalRepaymentAmount - item.amountBorrowed;
}

function totalInterestRate(item: DebtItem): number {
    if (item.amountBorrowed <= 0) {
        return 0;
    }

    return (totalInterestAmount(item) / item.amountBorrowed) * 100;
}

// Debts without a schedule are assumed to repeat monthly by default; we
// just don't have an anchor date to compute a real next payment date from,
// so fall back to assuming it's due sometime next month.
function nextPaymentLabel(item: DebtItem): string {
    if (item.nextPaymentDate) {
        return formatMonthYear(item.nextPaymentDate);
    }

    const today = new Date();
    const nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);

    return nextMonth.toLocaleDateString('en-US', {
        month: 'short',
        year: 'numeric',
    });
}

const expandedIds = reactive(new Set<number>());

function toggleExpanded(item: DebtItem) {
    if (expandedIds.has(item.id)) {
        expandedIds.delete(item.id);
    } else {
        expandedIds.add(item.id);
    }
}

function onArchiveOrRestore(item: DebtItem) {
    if (item.status === 'archived') {
        emit('restore', item);
    } else {
        emit('archive', item);
    }
}
</script>

<template>
    <div v-if="!items.length" class="box py-12 text-center text-sm opacity-50">
        No debts yet
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
                            Amount Borrowed
                        </th>
                        <th class="p-3 text-right font-medium">
                            Total Repayment Amount
                        </th>
                        <th class="p-3 text-right font-medium">
                            <SortButton
                                label="Next Repayment Amount"
                                :active="props.sort === 'amount'"
                                :direction="props.direction"
                                align-end
                                @click="emit('sort', 'amount')"
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
                            Monthly Interest
                        </th>
                        <th class="p-3 text-right font-medium">
                            Total Interest
                        </th>
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
                            <span
                                v-if="item.status === 'archived'"
                                class="ml-2 rounded-full bg-foreground/10 px-2 py-0.5 text-xs font-medium opacity-60"
                            >
                                Archived
                            </span>
                        </td>
                        <td class="p-3 text-right">
                            {{
                                formatCurrency(
                                    item.amountBorrowed,
                                    item.currencyCode,
                                )
                            }}
                        </td>
                        <td class="p-3 text-right">
                            <div>
                                {{
                                    formatCurrency(
                                        item.totalRepaymentAmount,
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
                                            item.balance,
                                            item.currencyCode,
                                        )
                                    }}
                                    remaining
                                </span>
                            </div>
                        </td>
                        <td class="p-3 text-right">
                            <div>
                                {{
                                    formatCurrency(
                                        item.monthlyRepaymentAmount,
                                        item.currencyCode,
                                    )
                                }}
                            </div>
                            <div class="mt-1 flex justify-end">
                                <span
                                    class="inline-block rounded-full bg-primary/10 px-2 py-0.5 text-xs font-normal text-primary"
                                >
                                    {{ nextPaymentLabel(item) }}
                                </span>
                            </div>
                        </td>
                        <td class="p-3 opacity-70">{{ item.categoryLabel }}</td>
                        <td class="p-3 text-right opacity-70">
                            <div>
                                {{
                                    formatCurrency(
                                        item.interestMonthly,
                                        item.currencyCode,
                                    )
                                }}
                            </div>
                            <div class="mt-1 flex justify-end">
                                <span
                                    class="inline-block rounded-full bg-foreground/10 px-2 py-0.5 text-xs font-normal opacity-70"
                                >
                                    {{ item.interestRate.toFixed(1) }}%
                                </span>
                            </div>
                        </td>
                        <td class="p-3 text-right opacity-70">
                            <div>
                                {{
                                    formatCurrency(
                                        totalInterestAmount(item),
                                        item.currencyCode,
                                    )
                                }}
                            </div>
                            <div class="mt-1 flex justify-end">
                                <span
                                    class="inline-block rounded-full bg-foreground/10 px-2 py-0.5 text-xs font-normal opacity-70"
                                >
                                    {{ totalInterestRate(item).toFixed(1) }}%
                                </span>
                            </div>
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
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem
                                            :disabled="!item.canRecordPayment"
                                            @click="
                                                item.canRecordPayment &&
                                                emit('recordPayment', item)
                                            "
                                        >
                                            <Banknote class="size-4" />
                                            Record Payment
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
        <div class="flex flex-col gap-2 md:hidden" data-testid="debt-cards">
            <div
                v-for="item in items"
                :key="item.id"
                class="box cursor-pointer p-3"
                data-testid="debt-card"
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
                            {{ item.categoryLabel }}
                        </div>
                    </div>
                    <div class="flex flex-none items-center gap-2">
                        <span class="font-medium">{{
                            formatCurrency(item.balance, item.currencyCode)
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
                            <span class="opacity-70">Next repayment</span>
                            <span class="font-medium">{{
                                nextPaymentLabel(item)
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="opacity-70">
                                Next repayment amount
                            </span>
                            <span class="font-medium">
                                {{
                                    formatCurrency(
                                        item.monthlyRepaymentAmount,
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
                            title="Record Payment"
                            :disabled="!item.canRecordPayment"
                            @click="emit('recordPayment', item)"
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
