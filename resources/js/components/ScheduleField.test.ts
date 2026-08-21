import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import ScheduleField from './ScheduleField.vue';
import type { RecurrenceOption, ScheduleValue } from './ScheduleField.vue';

const RECURRENCE_OPTIONS: RecurrenceOption[] = [
    { value: 'monthly', label: 'Monthly' },
    { value: 'every_n_months', label: 'Every few months' },
    { value: 'specific_months', label: 'On specific months' },
];

function mountField(modelValue: ScheduleValue = null, required = false) {
    return mount(ScheduleField, {
        props: {
            modelValue,
            recurrenceOptions: RECURRENCE_OPTIONS,
            required,
        },
    });
}

async function selectRecurrenceOption(
    wrapper: ReturnType<typeof mountField>,
    label: string,
) {
    const target = wrapper
        .findAll('[role="radio"]')
        .find((candidate) => candidate.text() === label);

    await target?.trigger('click');
}

// Monthly/specific-months schedules synthesize their start_date from
// today's year-month plus the chosen day, so assertions on the emitted
// value need to compute the expected anchor dynamically.
function todayYearMonth(): string {
    const today = new Date();

    return `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;
}

describe('ScheduleField', () => {
    it('shows a "Set Schedule" button in the empty state', () => {
        const wrapper = mountField(null);

        expect(wrapper.text()).toContain('Set Schedule');
        expect(wrapper.find('[role="radio"]').exists()).toBe(false);
    });

    it('shows the radio list once "Set Schedule" is clicked', async () => {
        const wrapper = mountField(null);

        await wrapper.get('button').trigger('click');

        expect(wrapper.text()).toContain('Does not repeat');
        expect(wrapper.text()).toContain('Monthly');
        expect(wrapper.text()).toContain('Every few months');
        expect(wrapper.text()).toContain('On specific months');
    });

    it('emits a one-time schedule when "Does not repeat" is picked with a date', async () => {
        const wrapper = mountField(null);
        await wrapper.get('button').trigger('click');

        await selectRecurrenceOption(wrapper, 'Does not repeat');
        await wrapper.get('#schedule-start-date').setValue('2026-09-15');

        const emitted = wrapper.emitted('update:modelValue');
        expect(emitted?.at(-1)).toEqual([
            {
                recurrence: null,
                startDate: '2026-09-15',
                endDate: null,
                reminderDaysBefore: null,
                intervalMonths: null,
                months: null,
            },
        ]);
        expect(wrapper.find('#schedule-end-date').exists()).toBe(false);
    });

    it('shows a day-of-month dropdown instead of a date picker once Monthly is picked', async () => {
        const wrapper = mountField(null);
        await wrapper.get('button').trigger('click');

        await selectRecurrenceOption(wrapper, 'Monthly');

        expect(wrapper.find('#schedule-start-date').exists()).toBe(false);
        await wrapper.get('#schedule-day-of-month').setValue('15');

        // No minimum tied to a start date, since there's no longer a
        // literal start date the user picked.
        expect(
            wrapper.get('#schedule-end-date').attributes('min'),
        ).toBeUndefined();

        await wrapper.get('#schedule-end-date').setValue('2026-06-15');

        const emitted = wrapper.emitted('update:modelValue');
        expect(emitted?.at(-1)).toEqual([
            {
                recurrence: 'monthly',
                startDate: `${todayYearMonth()}-15`,
                endDate: '2026-06-15',
                reminderDaysBefore: null,
                intervalMonths: null,
                months: null,
            },
        ]);
    });

    it('emits a reminder days count when set', async () => {
        const wrapper = mountField(null);
        await wrapper.get('button').trigger('click');

        await selectRecurrenceOption(wrapper, 'Does not repeat');
        await wrapper.get('#schedule-start-date').setValue('2026-09-15');
        await wrapper.get('#schedule-reminder-days-before').setValue('3');

        const emitted = wrapper.emitted('update:modelValue');
        expect(emitted?.at(-1)).toEqual([
            {
                recurrence: null,
                startDate: '2026-09-15',
                endDate: null,
                reminderDaysBefore: 3,
                intervalMonths: null,
                months: null,
            },
        ]);
    });

    it('emits an every-n-months schedule with the interval', async () => {
        const wrapper = mountField(null);
        await wrapper.get('button').trigger('click');

        await selectRecurrenceOption(wrapper, 'Every few months');
        await wrapper.get('#schedule-start-date').setValue('2026-03-01');
        await wrapper.get('#schedule-interval-months').setValue('3');

        const emitted = wrapper.emitted('update:modelValue');
        expect(emitted?.at(-1)).toEqual([
            {
                recurrence: 'every_n_months',
                startDate: '2026-03-01',
                endDate: null,
                reminderDaysBefore: null,
                intervalMonths: 3,
                months: null,
            },
        ]);
    });

    it('emits a specific-months schedule with the selected months and chosen day', async () => {
        const wrapper = mountField(null);
        await wrapper.get('button').trigger('click');

        await selectRecurrenceOption(wrapper, 'On specific months');

        const monthButtons = wrapper
            .findAll('button')
            .filter((button) => ['Jan', 'Apr'].includes(button.text()));
        await monthButtons[0].trigger('click');
        await monthButtons[1].trigger('click');
        await wrapper.get('#schedule-day-of-month').setValue('15');

        const emitted = wrapper.emitted('update:modelValue');
        expect(emitted?.at(-1)).toEqual([
            {
                recurrence: 'specific_months',
                startDate: `${todayYearMonth()}-15`,
                endDate: null,
                reminderDaysBefore: null,
                intervalMonths: null,
                months: [1, 4],
            },
        ]);
    });

    it('blocks "Done" when every-n-months is selected without an interval', async () => {
        const wrapper = mountField(null);
        await wrapper.get('button').trigger('click');

        await selectRecurrenceOption(wrapper, 'Every few months');
        await wrapper.get('#schedule-start-date').setValue('2026-03-01');

        const doneButton = wrapper
            .findAll('button')
            .find((button) => button.text() === 'Done');
        await doneButton?.trigger('click');

        expect(wrapper.text()).toContain(
            'Enter how many months to repeat every.',
        );
        expect(wrapper.find('#schedule-interval-months').exists()).toBe(true);
    });

    it('allows "Done" once the interval is filled in', async () => {
        const wrapper = mountField(null);
        await wrapper.get('button').trigger('click');

        await selectRecurrenceOption(wrapper, 'Every few months');
        await wrapper.get('#schedule-start-date').setValue('2026-03-01');
        await wrapper.get('#schedule-interval-months').setValue('3');

        const doneButton = wrapper
            .findAll('button')
            .find((button) => button.text() === 'Done');
        await doneButton?.trigger('click');

        expect(wrapper.find('#schedule-interval-months').exists()).toBe(false);
        expect(wrapper.text()).toContain('Repeats every 3 months');
    });

    it('blocks "Done" when specific-months is selected without any months chosen', async () => {
        const wrapper = mountField(null);
        await wrapper.get('button').trigger('click');

        await selectRecurrenceOption(wrapper, 'On specific months');

        const doneButton = wrapper
            .findAll('button')
            .find((button) => button.text() === 'Done');
        await doneButton?.trigger('click');

        expect(wrapper.text()).toContain('Select at least one month.');
    });

    it('does not require a start date for a specific-months schedule', async () => {
        const wrapper = mountField(null, true);

        await selectRecurrenceOption(wrapper, 'On specific months');
        const monthButton = wrapper
            .findAll('button')
            .find((button) => button.text() === 'Jan');
        await monthButton?.trigger('click');

        const doneButton = wrapper
            .findAll('button')
            .find((button) => button.text() === 'Done');
        await doneButton?.trigger('click');

        expect(wrapper.text()).not.toContain('Select a date.');
        expect(wrapper.text()).toContain('Repeats every Jan');
        expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([
            {
                recurrence: 'specific_months',
                startDate: `${todayYearMonth()}-01`,
                endDate: null,
                reminderDaysBefore: null,
                intervalMonths: null,
                months: [1],
            },
        ]);
    });

    it('shows a monthly summary once collapsed', async () => {
        const wrapper = mountField({
            recurrence: 'monthly',
            startDate: '2026-01-15',
            endDate: null,
            reminderDaysBefore: null,
            intervalMonths: null,
            months: null,
        });

        expect(wrapper.text()).toContain('Repeats monthly');
    });

    it('shows an every-n-months summary', async () => {
        const wrapper = mountField({
            recurrence: 'every_n_months',
            startDate: '2026-03-02',
            endDate: null,
            reminderDaysBefore: null,
            intervalMonths: 3,
            months: null,
        });

        expect(wrapper.text()).toContain(
            'Repeats every 3 months from Mar 2026',
        );
    });

    it('shows a specific-months summary listing the selected months', async () => {
        const wrapper = mountField({
            recurrence: 'specific_months',
            startDate: '2026-03-15',
            endDate: null,
            reminderDaysBefore: null,
            intervalMonths: null,
            months: [1, 3, 4],
        });

        expect(wrapper.text()).toContain('Repeats every Jan, Mar, Apr');
    });

    it('shows a one-time summary as a due date', async () => {
        const wrapper = mountField({
            recurrence: null,
            startDate: '2026-09-15',
            endDate: null,
            reminderDaysBefore: null,
            intervalMonths: null,
            months: null,
        });

        expect(wrapper.text()).toContain('Due Sep 2026');
    });

    it('appends an "until" suffix when an end date is set', async () => {
        const wrapper = mountField({
            recurrence: 'monthly',
            startDate: '2026-01-15',
            endDate: '2026-12-31',
            reminderDaysBefore: null,
            intervalMonths: null,
            months: null,
        });

        expect(wrapper.text()).toContain('Repeats monthly until Dec 2026');
    });

    it('reopens editing, pre-filled, when the pencil icon is clicked', async () => {
        const wrapper = mountField({
            recurrence: 'every_n_months',
            startDate: '2026-03-02',
            endDate: null,
            reminderDaysBefore: null,
            intervalMonths: 3,
            months: null,
        });

        await wrapper.get('button').trigger('click');

        expect(
            wrapper.get<HTMLInputElement>('#schedule-start-date').element.value,
        ).toBe('2026-03-02');
        expect(
            wrapper.get<HTMLInputElement>('#schedule-interval-months').element
                .value,
        ).toBe('3');
        expect(wrapper.text()).toContain('Every few months');
    });

    it('reopens editing with the day-of-month dropdown pre-filled for a monthly schedule', async () => {
        const wrapper = mountField({
            recurrence: 'monthly',
            startDate: '2026-03-17',
            endDate: null,
            reminderDaysBefore: null,
            intervalMonths: null,
            months: null,
        });

        await wrapper.get('button').trigger('click');

        expect(
            wrapper.get<HTMLSelectElement>('#schedule-day-of-month').element
                .value,
        ).toBe('17');
    });

    it('keeps the existing month/year anchor when only the day is changed', async () => {
        const wrapper = mountField({
            recurrence: 'monthly',
            startDate: '2026-03-17',
            endDate: null,
            reminderDaysBefore: null,
            intervalMonths: null,
            months: null,
        });

        await wrapper.get('button').trigger('click');
        await wrapper.get('#schedule-day-of-month').setValue('5');

        const emitted = wrapper.emitted('update:modelValue');
        expect(emitted?.at(-1)?.[0]).toMatchObject({ startDate: '2026-03-05' });
    });

    it('clears the schedule and emits null', async () => {
        const wrapper = mountField({
            recurrence: 'monthly',
            startDate: '2026-01-15',
            endDate: null,
            reminderDaysBefore: null,
            intervalMonths: null,
            months: null,
        });

        await wrapper.get('button').trigger('click');
        await wrapper.get('button.text-danger').trigger('click');

        expect(wrapper.text()).toContain('Set Schedule');
        expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([null]);
    });

    it('opens straight into editing (not a misleading summary) when a required schedule has no date yet', () => {
        const wrapper = mountField(
            {
                recurrence: 'monthly',
                startDate: null,
                endDate: null,
                reminderDaysBefore: null,
                intervalMonths: null,
                months: null,
            },
            true,
        );

        // A recurrence with no date isn't really a configured schedule -
        // showing "Repeats monthly" as a collapsed summary here previously
        // let users pick a recurrence, save, and have it silently dropped
        // since nothing renders without a start date.
        expect(wrapper.find('[role="radio"]').exists()).toBe(true);
        expect(wrapper.get('#schedule-day-of-month')).toBeTruthy();
    });

    it('excludes "Does not repeat" when required', () => {
        const wrapper = mountField(
            {
                recurrence: 'monthly',
                startDate: null,
                endDate: null,
                reminderDaysBefore: null,
                intervalMonths: null,
                months: null,
            },
            true,
        );

        expect(wrapper.text()).not.toContain('Does not repeat');
        expect(wrapper.text()).toContain('Monthly');
    });

    it('hides "Clear schedule" when required', () => {
        const wrapper = mountField(
            {
                recurrence: 'monthly',
                startDate: null,
                endDate: null,
                reminderDaysBefore: null,
                intervalMonths: null,
                months: null,
            },
            true,
        );

        expect(wrapper.find('button.text-danger').exists()).toBe(false);
    });

    it('still requires a real start date for every-n-months and one-time schedules', () => {
        const wrapper = mountField(null, true);

        // local.recurrence defaults to 'none' until a choice is made, which
        // takes the "real date picker" branch same as every-n-months does.
        expect(
            wrapper
                .get<HTMLInputElement>('#schedule-start-date')
                .attributes('required'),
        ).toBeDefined();
    });

    it('blocks "Done" when every-n-months is required but missing its start date', async () => {
        const wrapper = mountField(null, true);

        await selectRecurrenceOption(wrapper, 'Every few months');
        await wrapper.get('#schedule-interval-months').setValue('3');

        const doneButton = wrapper
            .findAll('button')
            .find((button) => button.text() === 'Done');
        await doneButton?.trigger('click');

        expect(wrapper.text()).toContain('Select a date.');
    });
});

// Wants is the first (and so far only) consumer of an optional,
// no-recurring-options schedule - a single "Does not repeat" choice with
// month/year granularity, since a Want is a one-off purchase target, not a
// recurring bill.
describe('ScheduleField with no recurring options (Wants-style usage)', () => {
    function mountOneTimeField(modelValue: ScheduleValue = null) {
        return mount(ScheduleField, {
            props: {
                modelValue,
                recurrenceOptions: [],
                required: false,
                dateGranularity: 'month',
                oneTimeLabel: 'Planned for',
            },
        });
    }

    it('skips the radio group when "Does not repeat" is the only choice', async () => {
        const wrapper = mountOneTimeField(null);

        await wrapper.get('button').trigger('click');

        expect(wrapper.find('[role="radio"]').exists()).toBe(false);
        expect(wrapper.text()).not.toContain('Does not repeat');
    });

    it('renders a month input instead of a full date picker', async () => {
        const wrapper = mountOneTimeField(null);

        await wrapper.get('button').trigger('click');

        const input = wrapper.get<HTMLInputElement>('#schedule-start-date');
        expect(input.attributes('type')).toBe('month');
        expect(wrapper.text()).toContain('Planned for');

        await input.setValue('2026-12');

        expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([
            {
                recurrence: null,
                startDate: '2026-12-01',
                endDate: null,
                reminderDaysBefore: null,
                intervalMonths: null,
                months: null,
            },
        ]);
    });

    it('shows the custom one-time label in the collapsed summary', () => {
        const wrapper = mountOneTimeField({
            recurrence: null,
            startDate: '2026-12-01',
            endDate: null,
            reminderDaysBefore: null,
            intervalMonths: null,
            months: null,
        });

        expect(wrapper.text()).toContain('Planned for Dec 2026');
    });
});
