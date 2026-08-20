import { DOMWrapper, mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import DebtsTable from './DebtsTable.vue';
import type { DebtItem } from './DebtsTable.vue';

function makeItem(overrides: Partial<DebtItem> = {}): DebtItem {
    return {
        id: 1,
        category: 'personal',
        categoryLabel: 'Personal Loan',
        name: 'Car loan',
        amountBorrowed: 500000,
        totalRepaymentAmount: 600000,
        monthlyRepaymentAmount: 25000,
        tenureMonths: 24,
        paymentsMade: 6,
        balance: 350000,
        remainingTenure: 18,
        interestMonthly: 4166.67,
        interestRate: 0.83,
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
        attachTo: document.body,
    });
}

// The row actions menu renders through a Teleport to <body>, so its items
// live outside the wrapper's own DOM subtree and have to be queried there.
async function openRowActions(wrapper: ReturnType<typeof mountTable>) {
    await wrapper.get('table tbody button[title="Actions"]').trigger('click');

    return new DOMWrapper(document.body);
}

function findMenuItem(menu: DOMWrapper<Element>, label: string) {
    const item = menu
        .findAll('[role="menuitem"]')
        .find((candidate) => candidate.text().includes(label));

    if (!item) {
        throw new Error(`Menu item "${label}" not found`);
    }

    return item;
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
                monthlyRepaymentAmount: 25000,
                balance: 350000,
                totalRepaymentAmount: 600000,
            }),
            makeItem({
                id: 2,
                name: 'Credit card',
                categoryLabel: 'Credit Card',
                monthlyRepaymentAmount: 10000,
                balance: 40000,
                totalRepaymentAmount: 100000,
            }),
        ]);

        const table = wrapper.get('table');

        expect(table.text()).toContain('Car loan');
        expect(table.text()).toContain('Auto Loan');
        expect(table.text()).toContain('₦25,000');
        expect(table.text()).toContain('₦350,000');
        expect(table.text()).toContain('₦600,000');
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

    it('emits recordPayment, edit, archive, and destroy from the row actions menu', async () => {
        const item = makeItem({ status: 'pending', canRecordPayment: true });
        const wrapper = mountTable([item]);

        await findMenuItem(
            await openRowActions(wrapper),
            'Record Payment',
        ).trigger('click');
        await findMenuItem(await openRowActions(wrapper), 'Edit').trigger(
            'click',
        );
        await findMenuItem(await openRowActions(wrapper), 'Archive').trigger(
            'click',
        );
        await findMenuItem(await openRowActions(wrapper), 'Delete').trigger(
            'click',
        );

        expect(wrapper.emitted('recordPayment')?.[0]).toEqual([item]);
        expect(wrapper.emitted('edit')?.[0]).toEqual([item]);
        expect(wrapper.emitted('archive')?.[0]).toEqual([item]);
        expect(wrapper.emitted('destroy')?.[0]).toEqual([item]);
    });

    it('emits restore instead of archive for an archived item', async () => {
        const item = makeItem({ status: 'archived' });
        const wrapper = mountTable([item]);

        await findMenuItem(await openRowActions(wrapper), 'Restore').trigger(
            'click',
        );

        expect(wrapper.emitted('restore')?.[0]).toEqual([item]);
        expect(wrapper.emitted('archive')).toBeUndefined();
    });

    it('disables the record payment menu item when canRecordPayment is false', async () => {
        const item = makeItem({ canRecordPayment: false });
        const wrapper = mountTable([item]);

        const recordPaymentItem = findMenuItem(
            await openRowActions(wrapper),
            'Record Payment',
        );

        expect(recordPaymentItem.attributes('data-disabled')).toBeDefined();

        await recordPaymentItem.trigger('click');

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
