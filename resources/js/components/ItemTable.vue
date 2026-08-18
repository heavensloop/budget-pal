<script setup lang="ts">
import StatusBadge from '@/components/StatusBadge.vue';
import { useCurrency } from '@/composables/useCurrency';

export type RecentItem = {
    name: string;
    category: string;
    amount: number;
    currencyCode: string;
    status: 'done' | 'pending' | 'skipped';
};

defineProps<{
    items: RecentItem[];
}>();

const { formatCurrency } = useCurrency();
</script>

<template>
    <div v-if="items.length" class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr
                    class="border-b border-foreground/10 text-xs uppercase opacity-60"
                >
                    <th class="pb-3 font-medium">Item</th>
                    <th class="pb-3 font-medium">Category</th>
                    <th class="pb-3 font-medium">Amount</th>
                    <th class="pb-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="item in items"
                    :key="item.name"
                    class="border-b border-foreground/5 last:border-0"
                >
                    <td class="py-3">{{ item.name }}</td>
                    <td class="py-3 opacity-70">{{ item.category }}</td>
                    <td class="py-3">
                        {{ formatCurrency(item.amount, item.currencyCode) }}
                    </td>
                    <td class="py-3"><StatusBadge :status="item.status" /></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div v-else class="py-8 text-center text-xs opacity-50">No items yet</div>
</template>
