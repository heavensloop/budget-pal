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

    it('reveals start/end dates and constrains the end date minimum once a recurring option is picked', async () => {
        const wrapper = mountField(null);
        await wrapper.get('button').trigger('click');

        await selectRecurrenceOption(wrapper, 'Monthly');
        await wrapper.get('#schedule-start-date').setValue('2026-01-15');

        expect(wrapper.get('#schedule-end-date').attributes('min')).toBe(
            '2026-01-15',
        );

        await wrapper.get('#schedule-end-date').setValue('2026-06-15');

        const emitted = wrapper.emitted('update:modelValue');
        expect(emitted?.at(-1)).toEqual([
            {
                recurrence: 'monthly',
                startDate: '2026-01-15',
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

    it('emits a specific-months schedule with the selected months', async () => {
        const wrapper = mountField(null);
        await wrapper.get('button').trigger('click');

        await selectRecurrenceOption(wrapper, 'On specific months');
        await wrapper.get('#schedule-start-date').setValue('2026-01-15');

        const monthButtons = wrapper
            .findAll('button')
            .filter((button) => ['Jan', 'Apr'].includes(button.text()));
        await monthButtons[0].trigger('click');
        await monthButtons[1].trigger('click');

        const emitted = wrapper.emitted('update:modelValue');
        expect(emitted?.at(-1)).toEqual([
            {
                recurrence: 'specific_months',
                startDate: '2026-01-15',
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
        await wrapper.get('#schedule-start-date').setValue('2026-01-15');

        const doneButton = wrapper
            .findAll('button')
            .find((button) => button.text() === 'Done');
        await doneButton?.trigger('click');

        expect(wrapper.text()).toContain('Select at least one month.');
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

    it('shows the recurrence summary when a recurrence is set but no date is chosen yet', () => {
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

        expect(wrapper.text()).toContain('Repeats monthly');
        expect(wrapper.text()).not.toContain('Not scheduled');
        expect(wrapper.text()).not.toContain('Set Schedule');
    });

    it('shows an every-n-months summary without a date reference when no date is chosen yet', () => {
        const wrapper = mountField(
            {
                recurrence: 'every_n_months',
                startDate: null,
                endDate: null,
                reminderDaysBefore: null,
                intervalMonths: 3,
                months: null,
            },
            true,
        );

        expect(wrapper.text()).toContain('Repeats every 3 months');
        expect(wrapper.text()).not.toContain('from');
    });

    it('excludes "Does not repeat" when required', async () => {
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

        await wrapper.get('button').trigger('click');

        expect(wrapper.text()).not.toContain('Does not repeat');
        expect(wrapper.text()).toContain('Monthly');
    });

    it('hides "Clear schedule" when required', async () => {
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

        await wrapper.get('button').trigger('click');

        expect(wrapper.find('button.text-danger').exists()).toBe(false);
    });

    it('allows leaving the start date blank for a recurring schedule', async () => {
        const wrapper = mountField(null, true);
        await wrapper.get('button').trigger('click');

        await selectRecurrenceOption(wrapper, 'Every few months');
        await wrapper.get('#schedule-interval-months').setValue('3');

        expect(
            wrapper
                .get<HTMLInputElement>('#schedule-start-date')
                .attributes('required'),
        ).toBeUndefined();
    });
});
