<?php

namespace Tests\Unit\Actions\Budget;

use App\Actions\Budget\MarkItemStatus;
use App\Enums\ItemStatus;
use App\Models\BudgetItem;
use App\Models\NeedsItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkItemStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_the_items_status()
    {
        $item = NeedsItem::factory()->create(['status' => ItemStatus::Pending]);

        $updated = app(MarkItemStatus::class)($item, ItemStatus::Done);

        $this->assertSame(ItemStatus::Done, $updated->status);
        $this->assertSame(ItemStatus::Done, $item->fresh()->status);
    }

    public function test_syncs_the_budget_item_projection()
    {
        $item = NeedsItem::factory()->create(['status' => ItemStatus::Pending]);

        app(MarkItemStatus::class)($item, ItemStatus::Skipped);

        $this->assertDatabaseHas('budget_items', [
            'source_type' => NeedsItem::class,
            'source_id' => $item->id,
            'status' => ItemStatus::Skipped->value,
        ]);
        $this->assertSame(1, BudgetItem::count());
    }
}
