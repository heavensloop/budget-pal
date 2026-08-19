import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import ScheduleField from './ScheduleField.vue';
import type { ScheduleValue } from './ScheduleField.vue';

function mountField(modelValue: ScheduleValue = null) {
    return mount(ScheduleField, {
        props: { modelValue },
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
        expect(wrapper.text()).toContain('Weekly');
        expect(wrapper.text()).toContain('Biweekly');
        expect(wrapper.text()).toContain('Monthly');
        expect(wrapper.text()).toContain('Yearly');
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
            },
        ]);
    });

    it('shows a monthly summary with the ordinal day once collapsed', async () => {
        const wrapper = mountField({
            recurrence: 'monthly',
            startDate: '2026-01-15',
            endDate: null,
            reminderDaysBefore: null,
        });

        expect(wrapper.text()).toContain('Repeats monthly on the 15th');
    });

    it('shows a weekly summary with the weekday name', async () => {
        // 2026-03-02 is a Monday.
        const wrapper = mountField({
            recurrence: 'weekly',
            startDate: '2026-03-02',
            endDate: null,
            reminderDaysBefore: null,
        });

        expect(wrapper.text()).toContain('Repeats weekly on Mondays');
    });

    it('shows a yearly summary with the month and day', async () => {
        const wrapper = mountField({
            recurrence: 'yearly',
            startDate: '2026-03-15',
            endDate: null,
            reminderDaysBefore: null,
        });

        expect(wrapper.text()).toContain('Repeats yearly on Mar 15');
    });

    it('shows a one-time summary as a due date', async () => {
        const wrapper = mountField({
            recurrence: null,
            startDate: '2026-09-15',
            endDate: null,
            reminderDaysBefore: null,
        });

        expect(wrapper.text()).toContain('Due Sep 15, 2026');
    });

    it('appends an "until" suffix when an end date is set', async () => {
        const wrapper = mountField({
            recurrence: 'monthly',
            startDate: '2026-01-15',
            endDate: '2026-12-31',
            reminderDaysBefore: null,
        });

        expect(wrapper.text()).toContain(
            'Repeats monthly on the 15th until Dec 31, 2026',
        );
    });

    it('reopens editing, pre-filled, when the pencil icon is clicked', async () => {
        const wrapper = mountField({
            recurrence: 'weekly',
            startDate: '2026-03-02',
            endDate: null,
            reminderDaysBefore: null,
        });

        await wrapper.get('button').trigger('click');

        expect(
            wrapper.get<HTMLInputElement>('#schedule-start-date').element.value,
        ).toBe('2026-03-02');
        expect(wrapper.text()).toContain('Weekly');
    });

    it('clears the schedule and emits null', async () => {
        const wrapper = mountField({
            recurrence: 'monthly',
            startDate: '2026-01-15',
            endDate: null,
            reminderDaysBefore: null,
        });

        await wrapper.get('button').trigger('click');
        await wrapper.get('button.text-danger').trigger('click');

        expect(wrapper.text()).toContain('Set Schedule');
        expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([null]);
    });
});
