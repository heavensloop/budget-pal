<script setup lang="ts">
import { ArcElement, Chart as ChartJS, Tooltip } from 'chart.js';
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import { resolveThemeColor } from '@/lib/resolveThemeColor';

ChartJS.register(ArcElement, Tooltip);

const props = defineProps<{
    pct: number;
    capLabel: string;
}>();

const severityVar = computed(() => {
    if (props.pct <= 0) {
        return null;
    }

    if (props.pct < 80) {
        return '--color-success';
    }

    if (props.pct <= 100) {
        return '--color-pending';
    }

    return '--color-danger';
});

const textColor = computed(() =>
    severityVar.value
        ? `var(${severityVar.value})`
        : 'color-mix(in oklch, var(--color-foreground), transparent 40%)',
);

const chartData = computed(() => {
    const filled = Math.min(Math.max(props.pct, 0), 100);
    const fillColor = severityVar.value
        ? resolveThemeColor(severityVar.value)
        : resolveThemeColor('--color-foreground', 0.15);

    return {
        datasets: [
            {
                data: [filled, 100 - filled],
                backgroundColor: [
                    fillColor,
                    resolveThemeColor('--color-foreground', 0.08),
                ],
                borderWidth: 0,
            },
        ],
    };
});

const chartOptions = {
    cutout: '82%',
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { enabled: false } },
};
</script>

<template>
    <div class="bp-gauge-wrap relative mx-auto size-42">
        <Doughnut :data="chartData" :options="chartOptions" />
        <div class="absolute inset-0 flex flex-col items-center justify-center">
            <div
                class="text-2xl leading-none font-bold"
                :style="{ color: textColor }"
            >
                {{ pct }}%
            </div>
            <div class="mt-1 text-center text-xs opacity-60">
                {{ capLabel }}
            </div>
        </div>
    </div>
</template>
