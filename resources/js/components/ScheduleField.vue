<script setup lang="ts">
import { CalendarPlus, Pencil } from '@lucide/vue';
import { reactive, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';

export type RecurrenceFrequency = 'monthly' | 'weekly' | 'biweekly' | 'yearly';

export type ScheduleValue = {
    recurrence: RecurrenceFrequency | null;
    startDate: string | null;
    endDate: string | null;
    reminderDaysBefore: number | null;
} | null;

const props = withDefaults(
    defineProps<{
        modelValue: ScheduleValue;
        name?: string;
        errors?: Record<string, string>;
    }>(),
    {
        name: 'schedule',
        errors: () => ({}),
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: ScheduleValue];
}>();

type RecurrenceChoice = RecurrenceFrequency | 'none';

const recurrenceOptions: { value: RecurrenceChoice; label: string }[] = [
    { value: 'none', label: 'Does not repeat' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'biweekly', label: 'Biweekly' },
    { value: 'monthly', label: 'Monthly' },
    { value: 'yearly', label: 'Yearly' },
];

// Rendered inside a <Form> keyed on the item being edited, so a fresh
// instance (and fresh local state) is guaranteed per edit session - no
// need to watch props.modelValue for external changes.
const isEditing = ref(false);

const local = reactive<{
    recurrence: RecurrenceChoice;
    startDate: string;
    endDate: string;
    reminderDaysBefore: string;
}>({
    recurrence: props.modelValue?.recurrence ?? 'none',
    startDate: props.modelValue?.startDate ?? '',
    endDate: props.modelValue?.endDate ?? '',
    reminderDaysBefore: props.modelValue?.reminderDaysBefore?.toString() ?? '',
});

watch(
    () => [
        local.recurrence,
        local.startDate,
        local.endDate,
        local.reminderDaysBefore,
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
        });
    },
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
    isEditing.value = false;
}

function parseLocalDate(value: string): Date {
    return new Date(`${value}T00:00:00`);
}

function formatDate(value: string): string {
    return parseLocalDate(value).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function formatMonthDay(value: string): string {
    return parseLocalDate(value).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
}

function formatWeekday(value: string): string {
    return parseLocalDate(value).toLocaleDateString('en-US', {
        weekday: 'long',
    });
}

function ordinal(day: number): string {
    if (day % 10 === 1 && day !== 11) {
        return `${day}st`;
    }

    if (day % 10 === 2 && day !== 12) {
        return `${day}nd`;
    }

    if (day % 10 === 3 && day !== 13) {
        return `${day}rd`;
    }

    return `${day}th`;
}

function summaryText(value: {
    recurrence: RecurrenceFrequency | null;
    startDate: string;
    endDate: string | null;
}): string {
    const suffix = value.endDate ? ` until ${formatDate(value.endDate)}` : '';

    switch (value.recurrence) {
        case 'monthly':
            return `Repeats monthly on the ${ordinal(parseLocalDate(value.startDate).getDate())}${suffix}`;
        case 'weekly':
            return `Repeats weekly on ${formatWeekday(value.startDate)}s${suffix}`;
        case 'biweekly':
            return `Repeats every 2 weeks from ${formatDate(value.startDate)}${suffix}`;
        case 'yearly':
            return `Repeats yearly on ${formatMonthDay(value.startDate)}${suffix}`;
        default:
            return `Due ${formatDate(value.startDate)}`;
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

        <Button
            v-if="!local.startDate && !isEditing"
            type="button"
            variant="outline"
            @click="startEditing"
        >
            <CalendarPlus class="size-4" />
            Set Schedule
        </Button>

        <div
            v-else-if="local.startDate && !isEditing"
            class="flex items-center justify-between gap-2 rounded-lg border border-foreground/10 px-3 py-2"
        >
            <span class="text-sm">{{
                summaryText({
                    recurrence:
                        local.recurrence === 'none' ? null : local.recurrence,
                    startDate: local.startDate,
                    endDate: local.endDate || null,
                })
            }}</span>
            <button
                type="button"
                class="rounded-lg p-1.5 hover:bg-foreground/5"
                @click="isEditing = true"
            >
                <Pencil class="size-4 opacity-60" />
            </button>
        </div>

        <div
            v-else
            class="grid gap-3 rounded-lg border border-foreground/10 p-3"
        >
            <RadioGroup v-model="local.recurrence">
                <label
                    v-for="option in recurrenceOptions"
                    :key="option.value"
                    class="flex items-center gap-2 text-sm"
                >
                    <RadioGroupItem :value="option.value" />
                    {{ option.label }}
                </label>
            </RadioGroup>

            <div class="grid gap-2">
                <Label :for="`${name}-start-date`">
                    {{ local.recurrence === 'none' ? 'Due date' : 'Starts on' }}
                </Label>
                <Input
                    :id="`${name}-start-date`"
                    v-model="local.startDate"
                    type="date"
                    required
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

            <div class="flex items-center justify-between">
                <button
                    type="button"
                    class="text-xs text-danger underline"
                    @click="clearSchedule"
                >
                    Clear schedule
                </button>
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    @click="isEditing = false"
                >
                    Done
                </Button>
            </div>
        </div>
    </div>
</template>
