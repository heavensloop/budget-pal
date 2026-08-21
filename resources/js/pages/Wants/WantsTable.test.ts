import { DOMWrapper, mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import WantsTable from './WantsTable.vue';
import type { SortColumn, WantItem } from './WantsTable.vue';

function makeItem(overrides: Partial<WantItem> = {}): WantItem {
    return {
        id: 1,
        name: 'New phone',
        category: 'electronics',
        categoryLabel: 'Electronics',
        amount: 500000,
        currencyCode: 'NGN',
        status: 'planned',
        statusLabel: 'Planned',
        position: 1,
        purchasedAt: null,
        notes: null,
        ...overrides,
    };
}

function mountTable(items: WantItem[], sort: SortColumn = 'position') {
    return mount(WantsTable, {
        props: {
            items,
            sort,
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

describe('WantsTable', () => {
    it('shows an empty state when there are no items', () => {
        const wrapper = mountTable([]);

        expect(wrapper.text()).toContain('No wants yet');
        expect(wrapper.find('table').exists()).toBe(false);
    });

    it('renders each item in the desktop table', () => {
        const wrapper = mountTable([
            makeItem({
                id: 1,
                name: 'New phone',
                categoryLabel: 'Electronics',
                amount: 500000,
            }),
            makeItem({
                id: 2,
                name: 'New shoes',
                categoryLabel: 'Clothing',
                amount: 50000,
            }),
        ]);

        const table = wrapper.get('table');

        expect(table.text()).toContain('New phone');
        expect(table.text()).toContain('Electronics');
        expect(table.text()).toContain('₦500,000');
        expect(table.text()).toContain('New shoes');
        expect(table.text()).toContain('Clothing');
    });

    it('shows the status badge for each item', () => {
        const wrapper = mountTable([makeItem({ statusLabel: 'Purchased' })]);

        expect(wrapper.get('table').text()).toContain('Purchased');
    });

    it('emits sort with the clicked column', async () => {
        const wrapper = mountTable([makeItem()]);

        await wrapper.get('table thead button').trigger('click');

        expect(wrapper.emitted('sort')).toEqual([['name']]);
    });

    it('shows move up/down buttons for a planned item when sorted by position', () => {
        const wrapper = mountTable(
            [makeItem({ status: 'planned' })],
            'position',
        );

        expect(
            wrapper.get('table tbody').find('button[title="Move up"]').exists(),
        ).toBe(true);
        expect(
            wrapper
                .get('table tbody')
                .find('button[title="Move down"]')
                .exists(),
        ).toBe(true);
    });

    it('hides move up/down buttons when sorted by something other than position', () => {
        const wrapper = mountTable([makeItem({ status: 'planned' })], 'name');

        expect(
            wrapper.get('table tbody').find('button[title="Move up"]').exists(),
        ).toBe(false);
    });

    it('hides move up/down buttons for a purchased item even when sorted by position', () => {
        const wrapper = mountTable(
            [makeItem({ status: 'purchased' })],
            'position',
        );

        expect(
            wrapper.get('table tbody').find('button[title="Move up"]').exists(),
        ).toBe(false);
    });

    it('emits reorder when move up/down is clicked', async () => {
        const item = makeItem({ status: 'planned' });
        const wrapper = mountTable([item], 'position');

        await wrapper
            .get('table tbody button[title="Move up"]')
            .trigger('click');
        await wrapper
            .get('table tbody button[title="Move down"]')
            .trigger('click');

        expect(wrapper.emitted('reorder')?.[0]).toEqual([item, 'up']);
        expect(wrapper.emitted('reorder')?.[1]).toEqual([item, 'down']);
    });

    it('emits markPurchased, edit, archive, and destroy from the row actions menu', async () => {
        const item = makeItem({ status: 'planned' });
        const wrapper = mountTable([item]);

        await findMenuItem(
            await openRowActions(wrapper),
            'Mark Purchased',
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

        expect(wrapper.emitted('markPurchased')?.[0]).toEqual([item]);
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

    it('disables the mark purchased menu item once already purchased', async () => {
        const item = makeItem({ status: 'purchased' });
        const wrapper = mountTable([item]);

        const markPurchasedItem = findMenuItem(
            await openRowActions(wrapper),
            'Mark Purchased',
        );

        expect(markPurchasedItem.attributes('data-disabled')).toBeDefined();

        await markPurchasedItem.trigger('click');

        expect(wrapper.emitted('markPurchased')).toBeUndefined();
    });

    it('shows name, category, amount, and status on a collapsed mobile card', () => {
        const wrapper = mountTable([
            makeItem({
                name: 'New phone',
                categoryLabel: 'Electronics',
                amount: 500000,
                statusLabel: 'Planned',
            }),
        ]);

        const card = wrapper.get('[data-testid="want-card"]');

        expect(card.text()).toContain('New phone');
        expect(card.text()).toContain('Electronics');
        expect(card.text()).toContain('₦500,000');
        expect(card.text()).toContain('Planned');
        expect(card.find('button').exists()).toBe(false);
    });

    it('reveals actions when a card is clicked', async () => {
        const wrapper = mountTable(
            [makeItem({ status: 'planned' })],
            'position',
        );

        const card = wrapper.get('[data-testid="want-card"]');
        await card.trigger('click');

        // Move up, move down, mark purchased, edit, archive, destroy
        expect(card.findAll('button')).toHaveLength(6);
    });

    it('collapses the card again on a second click', async () => {
        const wrapper = mountTable([makeItem()]);

        const card = wrapper.get('[data-testid="want-card"]');
        await card.trigger('click');
        await card.trigger('click');

        expect(card.find('button').exists()).toBe(false);
    });

    it('emits events from card actions without leaving the card expanded state broken', async () => {
        const item = makeItem({ status: 'archived' });
        const wrapper = mountTable([item]);

        const card = wrapper.get('[data-testid="want-card"]');
        await card.trigger('click');
        // No move buttons or "Mark Purchased" for an archived item, so the
        // first button is Edit.
        await card.findAll('button')[0].trigger('click');

        expect(wrapper.emitted('edit')?.[0]).toEqual([item]);
    });
});
