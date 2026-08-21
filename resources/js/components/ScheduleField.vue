<script setup lang="ts">
import { CalendarPlus, Pencil } from '@lucide/vue';
import { RadioGroupItem, RadioGroupRoot } from 'reka-ui';
import { computed, reactive, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type MonthlyRecurrence =
    'monthly' | 'every_n_months' | 'specific_months';

export type ScheduleValue = {
    recurrence: MonthlyRecurrence | null;
    startDate: string | null;
    endDate: string | null;
    reminderDaysBefore: number | null;
    intervalMonths: number | null;
    months: number[] | null;
} | null;

export type RecurrenceOption = { value: string; label: string };

const props = withDefaults(
    defineProps<{
        modelValue: ScheduleValue;
        name?: string;
        errors?: Record<string, string>;
        recurrenceOptions: RecurrenceOption[];
        required?: boolean;
        /** Granularity of the one-time (non-repeating) date field. */
        dateGranularity?: 'day' | 'month';
        /** Label for the one-time date field and its summary text. */
        oneTimeLabel?: string;
    }>(),
    {
        name: 'schedule',
        errors: () => ({}),
        required: false,
        dateGranularity: 'day',
        oneTimeLabel: 'Due',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: ScheduleValue];
}>();

type RecurrenceChoice = MonthlyRecurrence | 'none';

const MONTH_NAMES = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
];

const recurrenceChoices = computed<
    { value: RecurrenceChoice; label: string }[]
>(() => [
    ...(props.required
        ? []
        : [{ value: 'none' as RecurrenceChoice, label: 'Does not repeat' }]),
    ...props.recurrenceOptions.map((option) => ({
        value: option.value as RecurrenceChoice,
        label: option.label,
    })),
]);

// Rendered inside a <Form> keyed on the item being edited, so a fresh
// instance (and fresh local state) is guaranteed per edit session - no
// need to watch props.modelValue for external changes.
//
// A required schedule with no start date yet isn't really "set" - start
// in the editing view instead of the summary so it can't be mistaken for
// an already-configured schedule (which previously let users pick a
// recurrence, save, and have the whole schedule silently dropped because
// every hidden input is gated on a start date being present).
const isEditing = ref(props.required && !props.modelValue?.startDate);

const local = reactive<{
    recurrence: RecurrenceChoice;
    startDate: string;
    endDate: string;
    reminderDaysBefore: string;
    intervalMonths: string;
    months: number[];
}>({
    recurrence: props.modelValue?.recurrence ?? 'none',
    startDate: props.modelValue?.startDate ?? '',
    endDate: props.modelValue?.endDate ?? '',
    reminderDaysBefore: props.modelValue?.reminderDaysBefore?.toString() ?? '',
    intervalMonths: props.modelValue?.intervalMonths?.toString() ?? '',
    months: props.modelValue?.months ?? [],
});

// Monthly and specific-months schedules only ever care about the *day* of
// start_date (see IsSchedulableItem) - the month/year is just an anchor.
// So instead of making users pick a full date for these, we ask for the
// day of month directly and synthesize a start_date behind the scenes,
// anchored to whatever month/year is already there (or today, for a new
// schedule) - "Every few months" and a one-time due date still need a
// real date, since those genuinely depend on which month they start from.
const DAY_OPTIONS = Array.from({ length: 28 }, (_, index) => index + 1);

function ordinal(day: number): string {
    if (day % 100 >= 11 && day % 100 <= 13) {
        return `${day}th`;
    }

    switch (day % 10) {
        case 1:
            return `${day}st`;
        case 2:
            return `${day}nd`;
        case 3:
            return `${day}rd`;
        default:
            return `${day}th`;
    }
}

const dayOfMonth = ref(
    props.modelValue?.startDate
        ? String(parseLocalDate(props.modelValue.startDate).getDate())
        : '1',
);

// A one-time schedule with month-only granularity (e.g. Wants' purchase
// target) only cares about which month, not which day - so it's stored as
// day 1 of that month behind the scenes, same idea as the day-of-month
// synthesis above but for the month itself.
const targetMonth = computed<string>({
    get: () => (local.startDate ? local.startDate.slice(0, 7) : ''),
    set: (value: string) => {
        local.startDate = value ? `${value}-01` : '';
    },
});

function usesDayOfMonth(recurrence: RecurrenceChoice): boolean {
    return recurrence === 'monthly' || recurrence === 'specific_months';
}

function syncStartDateFromDay() {
    const base = local.startDate ? parseLocalDate(local.startDate) : new Date();
    const year = base.getFullYear();
    const month = base.getMonth() + 1;
    const daysInMonth = new Date(year, month, 0).getDate();
    const day = Math.min(Number(dayOfMonth.value) || 1, daysInMonth);

    local.startDate = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
}

if (usesDayOfMonth(local.recurrence)) {
    syncStartDateFromDay();
}

watch(dayOfMonth, () => {
    if (usesDayOfMonth(local.recurrence)) {
        syncStartDateFromDay();
    }
});

watch(
    () => local.recurrence,
    (recurrence, previous) => {
        if (usesDayOfMonth(recurrence) && recurrence !== previous) {
            dayOfMonth.value = local.startDate
                ? String(parseLocalDate(local.startDate).getDate())
                : '1';
            syncStartDateFromDay();
        }
    },
);

watch(
    () => [
        local.recurrence,
        local.startDate,
        local.endDate,
        local.reminderDaysBefore,
        local.intervalMonths,
        local.months,
    ],
    () => {
        if (!local.startDate) {
            emit('update:modelValue', null);

            return;
        }

        emit('update:modelValue', {
            recurrence: local.recurrence === 'none' ? null : local.recurrence,
            startDate: local.startDate,
            endDate: local.recurrence === 'none' ? null : local.endDate || null,
            reminderDaysBefore: local.reminderDaysBefore
                ? Number(local.reminderDaysBefore)
                : null,
            intervalMonths:
                local.recurrence === 'every_n_months' && local.intervalMonths
                    ? Number(local.intervalMonths)
                    : null,
            months:
                local.recurrence === 'specific_months' && local.months.length
                    ? local.months
                    : null,
        });
    },
    { deep: true },
);

function startEditing() {
    if (!local.startDate) {
        local.startDate = new Date().toISOString().slice(0, 10);
    }

    isEditing.value = true;
}

function clearSchedule() {
    local.recurrence = 'none';
    local.startDate = '';
    local.endDate = '';
    local.reminderDaysBefore = '';
    local.intervalMonths = '';
    local.months = [];
    dayOfMonth.value = '1';
    isEditing.value = false;
}

function toggleMonth(month: number) {
    local.months = local.months.includes(month)
        ? local.months.filter((selected) => selected !== month)
        : [...local.months, month].sort((a, b) => a - b);
}

const doneAttempted = ref(false);

const startDateMissing = computed(() => !local.startDate);

const intervalMonthsMissing = computed(
    () => local.recurrence === 'every_n_months' && !local.intervalMonths,
);

const monthsMissing = computed(
    () => local.recurrence === 'specific_months' && local.months.length === 0,
);

function finishEditing() {
    if (
        startDateMissing.value ||
        intervalMonthsMissing.value ||
        monthsMissing.value
    ) {
        doneAttempted.value = true;

        return;
    }

    isEditing.value = false;
}

function parseLocalDate(value: string): Date {
    return new Date(`${value}T00:00:00`);
}

function formatMonthYear(value: string): string {
    return parseLocalDate(value).toLocaleDateString('en-US', {
        month: 'short',
        year: 'numeric',
    });
}

function summaryText(value: {
    recurrence: MonthlyRecurrence | null;
    startDate: string;
    endDate: string | null;
    intervalMonths: number | null;
    months: number[] | null;
}): string {
    const suffix = value.endDate
        ? ` until ${formatMonthYear(value.endDate)}`
        : '';

    switch (value.recurrence) {
        case 'monthly':
            return `Repeats monthly${suffix}`;
        case 'every_n_months':
            return value.startDate
                ? `Repeats every ${value.intervalMonths} months from ${formatMonthYear(value.startDate)}${suffix}`
                : `Repeats every ${value.intervalMonths} months${suffix}`;
        case 'specific_months':
            return `Repeats every ${(value.months ?? [])
                .map((month) => MONTH_NAMES[month - 1])
                .join(', ')}${suffix}`;
        default:
            return `${props.oneTimeLabel} ${formatMonthYear(value.startDate)}`;
    }
}
</script>

<template>
    <div>
        <input
            v-if="local.startDate"
            type="hidden"
            :name="`${name}[start_date]`"
            :value="local.startDate"
        />
        <input
            v-if="local.startDate && local.recurrence !== 'none'"
            type="hidden"
            :name="`${name}[recurrence]`"
            :value="local.recurrence"
        />
        <input
            v-if="
                local.startDate && local.recurrence !== 'none' && local.endDate
            "
            type="hidden"
            :name="`${name}[end_date]`"
            :value="local.endDate"
        />
        <input
            v-if="local.startDate && local.reminderDaysBefore"
            type="hidden"
            :name="`${name}[reminder_days_before]`"
            :value="local.reminderDaysBefore"
        />
        <input
            v-if="
                local.startDate &&
                local.recurrence === 'every_n_months' &&
                local.intervalMonths
            "
            type="hidden"
            :name="`${name}[interval_months]`"
            :value="local.intervalMonths"
        />
        <template
            v-if="local.startDate && local.recurrence === 'specific_months'"
        >
            <input
                v-for="month in local.months"
                :key="month"
                type="hidden"
                :name="`${name}[months][]`"
                :value="month"
            />
        </template>

        <Button
            v-if="local.recurrence === 'none' && !local.startDate && !isEditing"
            type="button"
            variant="outline"
            @click="startEditing"
        >
            <CalendarPlus class="size-4" />
            Set Schedule
        </Button>

        <div
            v-else-if="
                (local.recurrence !== 'none' || local.startDate) && !isEditing
            "
            class="flex items-center justify-between gap-2 rounded-lg border border-foreground/10 px-3 py-2"
        >
            <span class="text-sm">{{
                summaryText({
                    recurrence:
                        local.recurrence === 'none' ? null : local.recurrence,
                    startDate: local.startDate,
                    endDate: local.endDate || null,
                    intervalMonths: local.intervalMonths
                        ? Number(local.intervalMonths)
                        : null,
                    months: local.months.length ? local.months : null,
                })
            }}</span>
            <button
                type="button"
                class="cursor-pointer rounded-lg p-1.5 hover:bg-foreground/5"
                @click="isEditing = true"
            >
                <Pencil class="size-4 opacity-60" />
            </button>
        </div>

        <div
            v-else
            class="grid gap-3 rounded-lg border border-foreground/10 p-3"
        >
            <RadioGroupRoot
                v-if="recurrenceChoices.length > 1"
                v-model="local.recurrence"
                class="flex flex-col gap-1.5"
            >
                <RadioGroupItem
                    v-for="option in recurrenceChoices"
                    :key="option.value"
                    :value="option.value"
                    class="group flex w-full cursor-pointer items-center gap-3 rounded-lg bg-foreground/5 px-3 py-2.5 text-left text-sm font-medium text-foreground transition-colors duration-150 outline-none hover:bg-foreground/10 focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <span
                        class="flex size-4 shrink-0 items-center justify-center rounded-full border border-foreground/30 transition-colors duration-150 group-data-[state=checked]:border-primary group-data-[state=checked]:bg-primary"
                    >
                        <span
                            class="size-1.5 rounded-full bg-primary-foreground opacity-0 transition-opacity duration-150 group-data-[state=checked]:opacity-100"
                        />
                    </span>
                    {{ option.label }}
                </RadioGroupItem>
            </RadioGroupRoot>

            <div
                v-if="local.recurrence === 'every_n_months'"
                class="grid gap-2"
            >
                <Label :for="`${name}-interval-months`">
                    Repeat every (months)
                </Label>
                <Input
                    :id="`${name}-interval-months`"
                    v-model="local.intervalMonths"
                    type="number"
                    min="2"
                    max="6"
                    required
                />
                <p
                    v-if="errors[`${name}.interval_months`]"
                    class="text-xs text-danger"
                >
                    {{ errors[`${name}.interval_months`] }}
                </p>
                <p
                    v-else-if="doneAttempted && intervalMonthsMissing"
                    class="text-xs text-danger"
                >
                    Enter how many months to repeat every.
                </p>
            </div>

            <div
                v-if="local.recurrence === 'specific_months'"
                class="grid gap-2"
            >
                <Label>Months</Label>
                <div class="grid grid-cols-4 gap-1.5">
                    <button
                        v-for="(monthName, index) in MONTH_NAMES"
                        :key="monthName"
                        type="button"
                        class="cursor-pointer rounded-lg border border-foreground/10 px-2 py-1.5 text-sm transition-colors duration-150 hover:bg-foreground/5"
                        :class="
                            local.months.includes(index + 1)
                                ? 'border-primary bg-primary text-primary-foreground hover:bg-primary'
                                : ''
                        "
                        @click="toggleMonth(index + 1)"
                    >
                        {{ monthName }}
                    </button>
                </div>
                <p v-if="errors[`${name}.months`]" class="text-xs text-danger">
                    {{ errors[`${name}.months`] }}
                </p>
                <p
                    v-else-if="doneAttempted && monthsMissing"
                    class="text-xs text-danger"
                >
                    Select at least one month.
                </p>
            </div>

            <div
                v-if="
                    local.recurrence === 'monthly' ||
                    local.recurrence === 'specific_months'
                "
                class="grid gap-2"
            >
                <Label :for="`${name}-day-of-month`">Day of month</Label>
                <select
                    :id="`${name}-day-of-month`"
                    v-model="dayOfMonth"
                    class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                >
                    <option
                        v-for="day in DAY_OPTIONS"
                        :key="day"
                        :value="String(day)"
                    >
                        {{ ordinal(day) }}
                    </option>
                </select>
            </div>

            <div
                v-else-if="
                    local.recurrence === 'none' && dateGranularity === 'month'
                "
                class="grid gap-2"
            >
                <Label :for="`${name}-start-date`">{{ oneTimeLabel }}</Label>
                <Input
                    :id="`${name}-start-date`"
                    v-model="targetMonth"
                    type="month"
                    :required="required"
                />
                <p
                    v-if="errors[`${name}.start_date`]"
                    class="text-xs text-danger"
                >
                    {{ errors[`${name}.start_date`] }}
                </p>
                <p
                    v-else-if="doneAttempted && startDateMissing"
                    class="text-xs text-danger"
                >
                    Select a month.
                </p>
            </div>

            <div v-else class="grid gap-2">
                <Label :for="`${name}-start-date`">
                    {{
                        local.recurrence === 'none' ? oneTimeLabel : 'Starts on'
                    }}
                </Label>
                <Input
                    :id="`${name}-start-date`"
                    v-model="local.startDate"
                    type="date"
                    :required="required"
                />
                <p
                    v-if="errors[`${name}.start_date`]"
                    class="text-xs text-danger"
                >
                    {{ errors[`${name}.start_date`] }}
                </p>
                <p
                    v-else-if="doneAttempted && startDateMissing"
                    class="text-xs text-danger"
                >
                    Select a date.
                </p>
            </div>

            <div v-if="local.recurrence !== 'none'" class="grid gap-2">
                <Label :for="`${name}-end-date`">End date (optional)</Label>
                <Input
                    :id="`${name}-end-date`"
                    v-model="local.endDate"
                    type="date"
                />
                <p
                    v-if="errors[`${name}.end_date`]"
                    class="text-xs text-danger"
                >
                    {{ errors[`${name}.end_date`] }}
                </p>
            </div>

            <div class="grid gap-2">
                <Label :for="`${name}-reminder-days-before`">
                    Remind me (days before, optional)
                </Label>
                <Input
                    :id="`${name}-reminder-days-before`"
                    v-model="local.reminderDaysBefore"
                    type="number"
                    min="0"
                    max="365"
                />
                <p
                    v-if="errors[`${name}.reminder_days_before`]"
                    class="text-xs text-danger"
                >
                    {{ errors[`${name}.reminder_days_before`] }}
                </p>
            </div>

            <div
                class="flex items-center"
                :class="required ? 'justify-end' : 'justify-between'"
            >
                <button
                    v-if="!required"
                    type="button"
                    class="cursor-pointer text-xs text-danger underline"
                    @click="clearSchedule"
                >
                    Clear schedule
                </button>
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    @click="finishEditing"
                >
                    Done
                </Button>
            </div>
        </div>
    </div>
</template>
