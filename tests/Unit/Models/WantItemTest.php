<?php

namespace Tests\Unit\Models;

use App\Actions\Budget\MarkWantItemStatus;
use App\Actions\Budget\ReorderWantItem;
use App\Enums\WantItemStatus;
use App\Models\User;
use App\Models\WantItem;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WantItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_purchased_sets_the_purchased_at_date()
    {
        $item = WantItem::factory()->create(['status' => WantItemStatus::PLANNED, 'purchased_at' => null]);

        $item = (new MarkWantItemStatus)($item, WantItemStatus::PURCHASED);

        $this->assertSame(WantItemStatus::PURCHASED, $item->status);
        $this->assertSame(CarbonImmutable::today()->toDateString(), $item->purchased_at->toDateString());
    }

    public function test_mark_purchased_does_not_overwrite_an_existing_purchased_at_date()
    {
        $item = WantItem::factory()->create([
            'status' => WantItemStatus::PLANNED,
            'purchased_at' => '2026-01-01',
        ]);

        $item = (new MarkWantItemStatus)($item, WantItemStatus::PURCHASED);

        $this->assertSame('2026-01-01', $item->purchased_at->toDateString());
    }

    public function test_marking_archived_leaves_an_existing_purchased_at_date_untouched()
    {
        $item = WantItem::factory()->create([
            'status' => WantItemStatus::PURCHASED,
            'purchased_at' => '2026-01-01',
        ]);

        $item = (new MarkWantItemStatus)($item, WantItemStatus::ARCHIVED);

        $this->assertSame(WantItemStatus::ARCHIVED, $item->status);
        $this->assertSame('2026-01-01', $item->purchased_at->toDateString());
    }

    public function test_marking_back_to_planned_clears_the_purchased_at_date()
    {
        $item = WantItem::factory()->create([
            'status' => WantItemStatus::PURCHASED,
            'purchased_at' => '2026-01-01',
        ]);

        $item = (new MarkWantItemStatus)($item, WantItemStatus::PLANNED);

        $this->assertNull($item->purchased_at);
    }

    public function test_reorder_up_swaps_position_with_the_planned_item_above_it()
    {
        $user = User::factory()->create();
        $first = WantItem::factory()->create(['user_id' => $user->id, 'position' => 1, 'status' => WantItemStatus::PLANNED]);
        $second = WantItem::factory()->create(['user_id' => $user->id, 'position' => 2, 'status' => WantItemStatus::PLANNED]);

        (new ReorderWantItem)($second, 'up');

        $this->assertSame(2, $first->fresh()->position);
        $this->assertSame(1, $second->fresh()->position);
    }

    public function test_reorder_down_swaps_position_with_the_planned_item_below_it()
    {
        $user = User::factory()->create();
        $first = WantItem::factory()->create(['user_id' => $user->id, 'position' => 1, 'status' => WantItemStatus::PLANNED]);
        $second = WantItem::factory()->create(['user_id' => $user->id, 'position' => 2, 'status' => WantItemStatus::PLANNED]);

        (new ReorderWantItem)($first, 'down');

        $this->assertSame(2, $first->fresh()->position);
        $this->assertSame(1, $second->fresh()->position);
    }

    public function test_reorder_skips_purchased_and_archived_items()
    {
        $user = User::factory()->create();
        $planned = WantItem::factory()->create(['user_id' => $user->id, 'position' => 1, 'status' => WantItemStatus::PLANNED]);
        $purchased = WantItem::factory()->create(['user_id' => $user->id, 'position' => 2, 'status' => WantItemStatus::PURCHASED]);
        $nextPlanned = WantItem::factory()->create(['user_id' => $user->id, 'position' => 3, 'status' => WantItemStatus::PLANNED]);

        (new ReorderWantItem)($planned, 'down');

        // Should swap with $nextPlanned (position 3), skipping the
        // purchased item sitting in between at position 2.
        $this->assertSame(3, $planned->fresh()->position);
        $this->assertSame(2, $purchased->fresh()->position);
        $this->assertSame(1, $nextPlanned->fresh()->position);
    }

    public function test_reorder_is_a_no_op_at_the_top_of_the_list()
    {
        $user = User::factory()->create();
        $first = WantItem::factory()->create(['user_id' => $user->id, 'position' => 1, 'status' => WantItemStatus::PLANNED]);
        WantItem::factory()->create(['user_id' => $user->id, 'position' => 2, 'status' => WantItemStatus::PLANNED]);

        (new ReorderWantItem)($first, 'up');

        $this->assertSame(1, $first->fresh()->position);
    }

    public function test_reorder_does_nothing_for_a_purchased_item()
    {
        $user = User::factory()->create();
        $purchased = WantItem::factory()->create(['user_id' => $user->id, 'position' => 1, 'status' => WantItemStatus::PURCHASED]);
        WantItem::factory()->create(['user_id' => $user->id, 'position' => 2, 'status' => WantItemStatus::PLANNED]);

        (new ReorderWantItem)($purchased, 'down');

        $this->assertSame(1, $purchased->fresh()->position);
    }
}
