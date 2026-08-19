<?php

namespace Tests\Unit\Actions\Budget;

use App\Actions\Budget\MarkItemStatus;
use App\Enums\NeedsItemStatus;
use App\Models\NeedsItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkItemStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_the_items_status()
    {
        $item = NeedsItem::factory()->create(['status' => NeedsItemStatus::Pending]);

        $updated = app(MarkItemStatus::class)($item, NeedsItemStatus::Done);

        $this->assertSame(NeedsItemStatus::Done, $updated->status);
        $this->assertSame(NeedsItemStatus::Done, $item->fresh()->status);
    }
}
