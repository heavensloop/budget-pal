import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import IncomeTable from './IncomeTable.vue';
import type { IncomeItem } from './IncomeTable.vue';

function makeItem(overrides: Partial<IncomeItem> = {}): IncomeItem {
    return {
        id: 1,
        category: 'salary',
        categoryLabel: 'Salary',
        name: 'Day job',
        amount: 350000,
        currencyCode: 'NGN',
        status: 'pending',
        schedule: {
            recurrence: 'monthly',
            startDate: '2026-01-01',
            endDate: null,
            reminderDaysBefore: null,
            intervalMonths: null,
            months: null,
        },
        nextPaymentDate: '2026-09-01',
        notes: null,
        ...overrides,
    };
}

function mountTable(items: IncomeItem[]) {
    return mount(IncomeTable, {
        props: {
            items,
            sort: 'name',
            direction: 'asc',
        },
    });
}

describe('IncomeTable', () => {
    it('shows an empty state when there are no items', () => {
        const wrapper = mountTable([]);

        expect(wrapper.text()).toContain('No income yet');
        expect(wrapper.find('table').exists()).toBe(false);
    });

    it('renders each item in the desktop table', () => {
        const wrapper = mountTable([
            makeItem({
                id: 1,
                name: 'Day job',
                categoryLabel: 'Salary',
                amount: 350000,
            }),
            makeItem({
                id: 2,
                name: 'Side gig',
                categoryLabel: 'Freelance',
                amount: 60000,
            }),
        ]);

        const table = wrapper.get('table');

        expect(table.text()).toContain('Day job');
        expect(table.text()).toContain('Salary');
        expect(table.text()).toContain('₦350,000');
        expect(table.text()).toContain('Side gig');
        expect(table.text()).toContain('Freelance');
    });

    it('marks archived items with a badge', () => {
        const wrapper = mountTable([makeItem({ status: 'archived' })]);

        expect(wrapper.get('table').text()).toContain('Archived');
    });

    it('assumes next month when there is no next payment date', () => {
        const wrapper = mountTable([makeItem({ nextPaymentDate: null })]);

        const today = new Date();
        const nextMonth = new Date(
            today.getFullYear(),
            today.getMonth() + 1,
            1,
        );
        const expectedLabel = nextMonth.toLocaleDateString('en-US', {
            month: 'short',
            year: 'numeric',
        });

        expect(wrapper.get('table').text()).toContain(expectedLabel);
        expect(wrapper.get('table').text()).not.toContain('Not scheduled');
    });

    it('emits sort with the clicked column', async () => {
        const wrapper = mountTable([makeItem()]);

        await wrapper.get('table thead button').trigger('click');

        expect(wrapper.emitted('sort')).toEqual([['name']]);
    });

    it('emits edit, archive, and destroy from the table row actions', async () => {
        const item = makeItem({ status: 'pending' });
        const wrapper = mountTable([item]);

        const buttons = wrapper.get('table tbody').findAll('button');
        await buttons[0].trigger('click'); // edit
        await buttons[1].trigger('click'); // archive
        await buttons[2].trigger('click'); // destroy

        expect(wrapper.emitted('edit')?.[0]).toEqual([item]);
        expect(wrapper.emitted('archive')?.[0]).toEqual([item]);
        expect(wrapper.emitted('destroy')?.[0]).toEqual([item]);
    });

    it('emits restore instead of archive for an archived item', async () => {
        const item = makeItem({ status: 'archived' });
        const wrapper = mountTable([item]);

        const buttons = wrapper.get('table tbody').findAll('button');
        await buttons[1].trigger('click'); // archive/restore toggle button

        expect(wrapper.emitted('restore')?.[0]).toEqual([item]);
        expect(wrapper.emitted('archive')).toBeUndefined();
    });

    it('shows only name, category, and amount on a collapsed mobile card', () => {
        const wrapper = mountTable([
            makeItem({
                name: 'Day job',
                categoryLabel: 'Salary',
                amount: 350000,
            }),
        ]);

        const card = wrapper.get('[data-testid="income-card"]');

        expect(card.text()).toContain('Day job');
        expect(card.text()).toContain('Salary');
        expect(card.text()).toContain('₦350,000');
        expect(card.find('button').exists()).toBe(false);
    });

    it('reveals actions and the next payment date when a card is clicked', async () => {
        const wrapper = mountTable([makeItem()]);

        const card = wrapper.get('[data-testid="income-card"]');
        await card.trigger('click');

        expect(card.findAll('button')).toHaveLength(3);
        expect(card.text()).toContain('Sep 2026');
    });

    it('collapses the card again on a second click', async () => {
        const wrapper = mountTable([makeItem()]);

        const card = wrapper.get('[data-testid="income-card"]');
        await card.trigger('click');
        await card.trigger('click');

        expect(card.find('button').exists()).toBe(false);
    });

    it('emits events from card actions without leaving the card expanded state broken', async () => {
        const item = makeItem();
        const wrapper = mountTable([item]);

        const card = wrapper.get('[data-testid="income-card"]');
        await card.trigger('click');
        await card.findAll('button')[0].trigger('click'); // edit

        expect(wrapper.emitted('edit')?.[0]).toEqual([item]);
    });
});
