<?php

namespace App\Http\Controllers\Web;

use App\Actions\Budget\CreateNeedsItem;
use App\Actions\Budget\MarkItemStatus;
use App\Actions\Budget\UpdateNeedsItem;
use App\Enums\CategoryType;
use App\Enums\NeedsItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Needs\ShowNeedsRequest;
use App\Http\Requests\Needs\StoreNeedsItemRequest;
use App\Http\Requests\Needs\UpdateNeedsItemRequest;
use App\Models\Category;
use App\Models\NeedsItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class NeedsController extends Controller
{
    public function index(ShowNeedsRequest $request): Response
    {
        $sort = $request->getSortField('name');
        $direction = $request->getSortDirection();
        $showArchived = $request->boolean('show_archived');

        $query = NeedsItem::query()
            ->where('user_id', $request->user()->id)
            ->with(['category', 'schedule'])
            ->sortable($sort, $direction);

        if (! $showArchived) {
            $query->where('status', '!=', NeedsItemStatus::Archived);
        }

        $items = $query->get()->map($this->mapItem(...));

        return Inertia::render('Needs/Index', [
            'categories' => Category::query()
                ->whereIn('type', [CategoryType::Need, CategoryType::Both])
                ->orderBy('name')
                ->get(['id', 'name']),
            'items' => $items,
            'sort' => $sort,
            'direction' => $direction->value,
            'showArchived' => $showArchived,
        ]);
    }

    public function store(StoreNeedsItemRequest $request, CreateNeedsItem $createNeedsItem): RedirectResponse
    {
        $createNeedsItem($request->user(), $request->validated());

        return back();
    }

    public function update(UpdateNeedsItemRequest $request, NeedsItem $need, UpdateNeedsItem $updateNeedsItem): RedirectResponse
    {
        $updateNeedsItem($need, $request->validated());

        return back();
    }

    public function destroy(NeedsItem $need): RedirectResponse
    {
        Gate::authorize('delete', $need);

        $need->delete();

        return back();
    }

    public function updateStatus(Request $request, NeedsItem $need, MarkItemStatus $markItemStatus): RedirectResponse
    {
        Gate::authorize('update', $need);

        $status = NeedsItemStatus::from($request->string('status')->toString());

        $markItemStatus($need, $status);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapItem(NeedsItem $item): array
    {
        return [
            'id' => $item->id,
            'categoryId' => $item->category_id,
            'category' => $item->category->name,
            'name' => $item->name,
            'amount' => (float) $item->amount,
            'currencyCode' => $item->currency_code,
            'status' => $item->status->value,
            'isRecurring' => (bool) $item->schedule_id,
            'dueDay' => $item->schedule?->due_day,
            'dateDue' => $item->date_due?->toDateString(),
            'nextPaymentDate' => $item->nextPaymentDate()?->toDateString(),
            'notes' => $item->notes,
        ];
    }
}
