import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import DebtsTable from './DebtsTable.vue';
import type { DebtItem } from './DebtsTable.vue';

function makeItem(overrides: Partial<DebtItem> = {}): DebtItem {
    return {
        id: 1,
        category: 'personal',
        categoryLabel: 'Personal Loan',
        name: 'Car loan',
        principal: 500000,
        balance: 350000,
        amount: 25000,
        currencyCode: 'NGN',
        status: 'pending',
        lastPaymentDate: null,
        schedule: {
            recurrence: 'monthly',
            startDate: '2026-01-01',
            endDate: null,
            reminderDaysBefore: null,
            intervalMonths: null,
            months: null,
        },
        nextPaymentDate: '2026-09-01',
        canRecordPayment: true,
        notes: null,
        ...overrides,
    };
}

function mountTable(items: DebtItem[]) {
    return mount(DebtsTable, {
        props: {
            items,
            sort: 'name',
            direction: 'asc',
        },
    });
}

describe('DebtsTable', () => {
    it('shows an empty state when there are no items', () => {
        const wrapper = mountTable([]);

        expect(wrapper.text()).toContain('No debts yet');
        expect(wrapper.find('table').exists()).toBe(false);
    });

    it('renders each item in the desktop table', () => {
        const wrapper = mountTable([
            makeItem({
                id: 1,
                name: 'Car loan',
                categoryLabel: 'Auto Loan',
                amount: 25000,
                balance: 350000,
                principal: 500000,
            }),
            makeItem({
                id: 2,
                name: 'Credit card',
                categoryLabel: 'Credit Card',
                amount: 10000,
                balance: 40000,
                principal: 100000,
            }),
        ]);

        const table = wrapper.get('table');

        expect(table.text()).toContain('Car loan');
        expect(table.text()).toContain('Auto Loan');
        expect(table.text()).toContain('₦25,000');
        expect(table.text()).toContain('₦350,000');
        expect(table.text()).toContain('₦500,000');
        expect(table.text()).toContain('Credit card');
        expect(table.text()).toContain('Credit Card');
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

    it('emits recordPayment, edit, archive, and destroy from the table row actions', async () => {
        const item = makeItem({ status: 'pending', canRecordPayment: true });
        const wrapper = mountTable([item]);

        const buttons = wrapper.get('table tbody').findAll('button');
        await buttons[0].trigger('click'); // record payment
        await buttons[1].trigger('click'); // edit
        await buttons[2].trigger('click'); // archive
        await buttons[3].trigger('click'); // destroy

        expect(wrapper.emitted('recordPayment')?.[0]).toEqual([item]);
        expect(wrapper.emitted('edit')?.[0]).toEqual([item]);
        expect(wrapper.emitted('archive')?.[0]).toEqual([item]);
        expect(wrapper.emitted('destroy')?.[0]).toEqual([item]);
    });

    it('emits restore instead of archive for an archived item', async () => {
        const item = makeItem({ status: 'archived' });
        const wrapper = mountTable([item]);

        const buttons = wrapper.get('table tbody').findAll('button');
        await buttons[2].trigger('click'); // archive/restore toggle button

        expect(wrapper.emitted('restore')?.[0]).toEqual([item]);
        expect(wrapper.emitted('archive')).toBeUndefined();
    });

    it('disables the record payment button when canRecordPayment is false', async () => {
        const item = makeItem({ canRecordPayment: false });
        const wrapper = mountTable([item]);

        const recordPaymentButton = wrapper.get(
            'table tbody button[title="Record Payment"]',
        );

        expect(recordPaymentButton.attributes('disabled')).toBeDefined();

        await recordPaymentButton.trigger('click');

        expect(wrapper.emitted('recordPayment')).toBeUndefined();
    });

    it('shows only name, category, and balance on a collapsed mobile card', () => {
        const wrapper = mountTable([
            makeItem({
                name: 'Car loan',
                categoryLabel: 'Auto Loan',
                balance: 350000,
            }),
        ]);

        const card = wrapper.get('[data-testid="debt-card"]');

        expect(card.text()).toContain('Car loan');
        expect(card.text()).toContain('Auto Loan');
        expect(card.text()).toContain('₦350,000');
        expect(card.find('button').exists()).toBe(false);
    });

    it('reveals actions and the next payment date when a card is clicked', async () => {
        const wrapper = mountTable([makeItem()]);

        const card = wrapper.get('[data-testid="debt-card"]');
        await card.trigger('click');

        expect(card.findAll('button')).toHaveLength(4);
        expect(card.text()).toContain('Sep 2026');
    });

    it('collapses the card again on a second click', async () => {
        const wrapper = mountTable([makeItem()]);

        const card = wrapper.get('[data-testid="debt-card"]');
        await card.trigger('click');
        await card.trigger('click');

        expect(card.find('button').exists()).toBe(false);
    });

    it('emits events from card actions without leaving the card expanded state broken', async () => {
        const item = makeItem();
        const wrapper = mountTable([item]);

        const card = wrapper.get('[data-testid="debt-card"]');
        await card.trigger('click');
        await card.findAll('button')[1].trigger('click'); // edit

        expect(wrapper.emitted('edit')?.[0]).toEqual([item]);
    });
});
