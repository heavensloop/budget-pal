import { DOMWrapper, mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import SavingsTable from './SavingsTable.vue';
import type { SavingsItem } from './SavingsTable.vue';

function makeItem(overrides: Partial<SavingsItem> = {}): SavingsItem {
    return {
        id: 1,
        type: 'savings',
        typeLabel: 'Savings',
        name: 'Emergency fund',
        targetAmount: 500000,
        installmentAmount: 25000,
        installmentsMade: 6,
        targetProfit: 20000,
        maturityDate: '2027-01-01',
        amountSaved: 150000,
        remainingToTarget: 350000,
        profitEarned: 6000,
        currencyCode: 'NGN',
        status: 'ongoing',
        statusLabel: 'Ongoing',
        lastContributionDate: null,
        schedule: {
            recurrence: 'monthly',
            startDate: '2026-01-01',
            endDate: null,
            reminderDaysBefore: null,
            intervalMonths: null,
            months: null,
        },
        nextContributionDate: '2026-09-01',
        canRecordContribution: true,
        notes: null,
        ...overrides,
    };
}

function mountTable(items: SavingsItem[]) {
    return mount(SavingsTable, {
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

describe('SavingsTable', () => {
    it('shows an empty state when there are no items', () => {
        const wrapper = mountTable([]);

        expect(wrapper.text()).toContain('No savings or investments yet');
        expect(wrapper.find('table').exists()).toBe(false);
    });

    it('renders each item in the desktop table', () => {
        const wrapper = mountTable([
            makeItem({
                id: 1,
                name: 'Emergency fund',
                typeLabel: 'Savings',
                targetAmount: 500000,
                amountSaved: 150000,
                installmentAmount: 25000,
            }),
            makeItem({
                id: 2,
                name: 'Index fund',
                typeLabel: 'Investment',
                targetAmount: 1000000,
                amountSaved: 100000,
                installmentAmount: 50000,
            }),
        ]);

        const table = wrapper.get('table');

        expect(table.text()).toContain('Emergency fund');
        expect(table.text()).toContain('Savings');
        expect(table.text()).toContain('₦500,000');
        expect(table.text()).toContain('₦150,000');
        expect(table.text()).toContain('₦25,000');
        expect(table.text()).toContain('Index fund');
        expect(table.text()).toContain('Investment');
    });

    it('shows the status badge for each item', () => {
        const wrapper = mountTable([makeItem({ statusLabel: 'Completed' })]);

        expect(wrapper.get('table').text()).toContain('Completed');
    });

    it('shows a dash for target profit when none is set', () => {
        const wrapper = mountTable([
            makeItem({ targetProfit: null, profitEarned: 0 }),
        ]);

        expect(wrapper.get('table').text()).toContain('—');
    });

    it('assumes next month when there is no next contribution date', () => {
        const wrapper = mountTable([makeItem({ nextContributionDate: null })]);

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
    });

    it('emits sort with the clicked column', async () => {
        const wrapper = mountTable([makeItem()]);

        await wrapper.get('table thead button').trigger('click');

        expect(wrapper.emitted('sort')).toEqual([['name']]);
    });

    it('emits recordContribution, edit, archive, and destroy from the row actions menu', async () => {
        const item = makeItem({
            status: 'ongoing',
            canRecordContribution: true,
        });
        const wrapper = mountTable([item]);

        await findMenuItem(
            await openRowActions(wrapper),
            'Record Contribution',
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

        expect(wrapper.emitted('recordContribution')?.[0]).toEqual([item]);
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

    it('disables the record contribution menu item when canRecordContribution is false', async () => {
        const item = makeItem({ canRecordContribution: false });
        const wrapper = mountTable([item]);

        const recordContributionItem = findMenuItem(
            await openRowActions(wrapper),
            'Record Contribution',
        );

        expect(
            recordContributionItem.attributes('data-disabled'),
        ).toBeDefined();

        await recordContributionItem.trigger('click');

        expect(wrapper.emitted('recordContribution')).toBeUndefined();
    });

    it('shows only name, type, and amount saved on a collapsed mobile card', () => {
        const wrapper = mountTable([
            makeItem({
                name: 'Emergency fund',
                typeLabel: 'Savings',
                amountSaved: 150000,
            }),
        ]);

        const card = wrapper.get('[data-testid="savings-card"]');

        expect(card.text()).toContain('Emergency fund');
        expect(card.text()).toContain('Savings');
        expect(card.text()).toContain('₦150,000');
        expect(card.find('button').exists()).toBe(false);
    });

    it('reveals actions and the next contribution date when a card is clicked', async () => {
        const wrapper = mountTable([makeItem()]);

        const card = wrapper.get('[data-testid="savings-card"]');
        await card.trigger('click');

        expect(card.findAll('button')).toHaveLength(4);
        expect(card.text()).toContain('Sep 2026');
    });

    it('collapses the card again on a second click', async () => {
        const wrapper = mountTable([makeItem()]);

        const card = wrapper.get('[data-testid="savings-card"]');
        await card.trigger('click');
        await card.trigger('click');

        expect(card.find('button').exists()).toBe(false);
    });

    it('emits events from card actions without leaving the card expanded state broken', async () => {
        const item = makeItem();
        const wrapper = mountTable([item]);

        const card = wrapper.get('[data-testid="savings-card"]');
        await card.trigger('click');
        await card.findAll('button')[1].trigger('click'); // edit

        expect(wrapper.emitted('edit')?.[0]).toEqual([item]);
    });
});
