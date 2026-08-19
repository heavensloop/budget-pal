import { router } from '@inertiajs/vue3';
import { computed, unref } from 'vue';
import type { Ref } from 'vue';

export type CurrentMonth = {
    year: number;
    month: number;
    label: string;
};

export function useMonthSelector(
    currentMonth:
        Ref<CurrentMonth | null | undefined> | CurrentMonth | null | undefined,
) {
    const label = computed(() => unref(currentMonth)?.label ?? '');

    /**
     * Stays on whichever page the switcher is used from (Dashboard, Needs,
     * ...) - only the year/month query params change.
     */
    function visit(year: number, month: number) {
        const path = window.location.pathname;

        router.visit(`${path}?year=${year}&month=${month}`, {
            preserveState: true,
            preserveScroll: true,
        });
    }

    function goToPreviousMonth() {
        const value = unref(currentMonth);

        if (!value) {
            return;
        }

        const date = new Date(value.year, value.month - 2, 1);
        visit(date.getFullYear(), date.getMonth() + 1);
    }

    function goToNextMonth() {
        const value = unref(currentMonth);

        if (!value) {
            return;
        }

        const date = new Date(value.year, value.month, 1);
        visit(date.getFullYear(), date.getMonth() + 1);
    }

    return { label, goToPreviousMonth, goToNextMonth };
}
