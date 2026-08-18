<script setup lang="ts">
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    LinearScale,
    Tooltip,
} from 'chart.js';
import type { TooltipItem } from 'chart.js';
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import { useCurrency } from '@/composables/useCurrency';
import { resolveThemeColor } from '@/lib/resolveThemeColor';

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip);

const props = defineProps<{
    labels: string[];
    amounts: number[];
    currencyCode?: string;
}>();

const { formatCurrency } = useCurrency();

function hueSteps(n: number): string[] {
    const stops = [1, 0.82, 0.66, 0.52, 0.4, 0.3, 0.22];

    return Array.from({ length: n }, (_, i) => {
        const o = stops[Math.min(i, stops.length - 1)];

        return resolveThemeColor('--color-primary', o);
    });
}

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            data: props.amounts,
            backgroundColor: hueSteps(props.labels.length),
            borderRadius: 4,
            barThickness: 14,
        },
    ],
}));

const chartOptions = computed(() => ({
    indexAxis: 'y' as const,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx: TooltipItem<'bar'>) =>
                    formatCurrency(ctx.parsed.x ?? 0, props.currencyCode),
            },
        },
    },
    scales: {
        x: {
            ticks: {
                callback: (value: string | number) =>
                    formatCurrency(Number(value), props.currencyCode),
                font: { size: 10 },
            },
            grid: { display: false },
            border: { display: false },
        },
        y: {
            ticks: { font: { size: 11 } },
            grid: { display: false },
            border: { display: false },
        },
    },
}));
</script>

<template>
    <div class="h-52.5">
        <Bar v-if="labels.length" :data="chartData" :options="chartOptions" />
        <div
            v-else
            class="flex h-full items-center justify-center text-xs opacity-50"
        >
            No items yet
        </div>
    </div>
</template>
