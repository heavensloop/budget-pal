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
    }>(),
    {
        name: 'schedule',
        errors: () => ({}),
        required: false,
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
const isEditing = ref(false);

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
    isEditing.value = false;
}

function toggleMonth(month: number) {
    local.months = local.months.includes(month)
        ? local.months.filter((selected) => selected !== month)
        : [...local.months, month].sort((a, b) => a - b);
}

const doneAttempted = ref(false);

const intervalMonthsMissing = computed(
    () => local.recurrence === 'every_n_months' && !local.intervalMonths,
);

const monthsMissing = computed(
    () => local.recurrence === 'specific_months' && local.months.length === 0,
);

function finishEditing() {
    if (intervalMonthsMissing.value || monthsMissing.value) {
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
            return `Due ${formatMonthYear(value.startDate)}`;
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

            <div class="grid gap-2">
                <Label :for="`${name}-start-date`">
                    {{ local.recurrence === 'none' ? 'Due date' : 'Starts on' }}
                </Label>
                <Input
                    :id="`${name}-start-date`"
                    v-model="local.startDate"
                    type="date"
                />
                <p
                    v-if="errors[`${name}.start_date`]"
                    class="text-xs text-danger"
                >
                    {{ errors[`${name}.start_date`] }}
                </p>
            </div>

            <div v-if="local.recurrence !== 'none'" class="grid gap-2">
                <Label :for="`${name}-end-date`">End date (optional)</Label>
                <Input
                    :id="`${name}-end-date`"
                    v-model="local.endDate"
                    type="date"
                    :min="local.startDate || undefined"
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
