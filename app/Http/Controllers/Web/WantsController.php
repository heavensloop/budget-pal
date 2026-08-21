<?php

namespace App\Http\Controllers\Web;

use App\Actions\Budget\CreateWantItem;
use App\Actions\Budget\MarkWantItemStatus;
use App\Actions\Budget\ReorderWantItem;
use App\Actions\Budget\UpdateWantItem;
use App\Enums\WantCategory;
use App\Enums\WantItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wants\ShowWantsRequest;
use App\Http\Requests\Wants\StoreWantItemRequest;
use App\Http\Requests\Wants\UpdateWantItemRequest;
use App\Models\WantItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WantsController extends Controller
{
    public function index(ShowWantsRequest $request): Response
    {
        $sort = $request->getSortField('position');
        $direction = $request->getSortDirection();
        $showArchived = $request->boolean('show_archived');

        $query = WantItem::query()
            ->where('user_id', $request->user()->id)
            ->sortable($sort, $direction);

        if (! $showArchived) {
            $query->where('status', '!=', WantItemStatus::ARCHIVED);
        }

        $items = $query->get()->map($this->mapItem(...));

        return Inertia::render('Wants/Index', [
            'categoryOptions' => WantCategory::options(),
            'statusOptions' => WantItemStatus::options(),
            'items' => $items,
            'sort' => $sort,
            'direction' => $direction->value,
            'showArchived' => $showArchived,
        ]);
    }

    public function store(StoreWantItemRequest $request, CreateWantItem $createWantItem): RedirectResponse
    {
        $createWantItem($request->user(), $request->validated());

        return back();
    }

    public function update(UpdateWantItemRequest $request, WantItem $want, UpdateWantItem $updateWantItem): RedirectResponse
    {
        $updateWantItem($want, $request->validated());

        return back();
    }

    public function destroy(WantItem $want): RedirectResponse
    {
        Gate::authorize('delete', $want);

        $want->delete();

        return back();
    }

    public function updateStatus(Request $request, WantItem $want, MarkWantItemStatus $markWantItemStatus): RedirectResponse
    {
        Gate::authorize('update', $want);

        $status = WantItemStatus::from($request->string('status')->toString());

        $markWantItemStatus($want, $status);

        return back();
    }

    public function reorder(Request $request, WantItem $want, ReorderWantItem $reorderWantItem): RedirectResponse
    {
        Gate::authorize('update', $want);

        $direction = $request->string('direction')->toString();

        if (! in_array($direction, ['up', 'down'], true)) {
            return back()->withErrors(['direction' => 'Invalid reorder direction.']);
        }

        $reorderWantItem($want, $direction);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapItem(WantItem $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'category' => $item->category->value,
            'categoryLabel' => $item->category->getReadable(),
            'amount' => (float) $item->amount,
            'currencyCode' => $item->currency_code,
            'status' => $item->status->value,
            'statusLabel' => $item->status->getReadable(),
            'position' => $item->position,
            'purchasedAt' => $item->purchased_at?->toDateString(),
            'notes' => $item->notes,
        ];
    }
}
