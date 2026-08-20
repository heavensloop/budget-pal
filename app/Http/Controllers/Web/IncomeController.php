<?php

namespace App\Http\Controllers\Web;

use App\Actions\Budget\CreateIncomeItem;
use App\Actions\Budget\MarkIncomeItemStatus;
use App\Actions\Budget\UpdateIncomeItem;
use App\Enums\IncomeCategory;
use App\Enums\IncomeItemStatus;
use App\Enums\MonthlyRecurrence;
use App\Http\Controllers\Controller;
use App\Http\Requests\Income\ShowIncomeRequest;
use App\Http\Requests\Income\StoreIncomeItemRequest;
use App\Http\Requests\Income\UpdateIncomeItemRequest;
use App\Models\IncomeItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class IncomeController extends Controller
{
    public function index(ShowIncomeRequest $request): Response
    {
        $sort = $request->getSortField('name');
        $direction = $request->getSortDirection();
        $showArchived = $request->boolean('show_archived');

        $query = IncomeItem::query()
            ->where('user_id', $request->user()->id)
            ->with(['schedule'])
            ->sortable($sort, $direction);

        if (! $showArchived) {
            $query->where('status', '!=', IncomeItemStatus::Archived);
        }

        $items = $query->get()->map($this->mapItem(...));

        return Inertia::render('Income/Index', [
            'categoryOptions' => IncomeCategory::options(),
            'items' => $items,
            'sort' => $sort,
            'direction' => $direction->value,
            'showArchived' => $showArchived,
            'recurrenceOptions' => MonthlyRecurrence::options([
                MonthlyRecurrence::Monthly,
                MonthlyRecurrence::EveryNMonths,
                MonthlyRecurrence::SpecificMonths,
            ]),
        ]);
    }

    public function store(StoreIncomeItemRequest $request, CreateIncomeItem $createIncomeItem): RedirectResponse
    {
        $createIncomeItem($request->user(), $request->validated());

        return back();
    }

    public function update(UpdateIncomeItemRequest $request, IncomeItem $incomeItem, UpdateIncomeItem $updateIncomeItem): RedirectResponse
    {
        $updateIncomeItem($incomeItem, $request->validated());

        return back();
    }

    public function destroy(IncomeItem $incomeItem): RedirectResponse
    {
        Gate::authorize('delete', $incomeItem);

        $incomeItem->delete();

        return back();
    }

    public function updateStatus(Request $request, IncomeItem $incomeItem, MarkIncomeItemStatus $markIncomeItemStatus): RedirectResponse
    {
        Gate::authorize('update', $incomeItem);

        $status = IncomeItemStatus::from($request->string('status')->toString());

        $markIncomeItemStatus($incomeItem, $status);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapItem(IncomeItem $item): array
    {
        return [
            'id' => $item->id,
            'category' => $item->category->value,
            'categoryLabel' => $item->category->getReadable(),
            'name' => $item->name,
            'amount' => (float) $item->amount,
            'currencyCode' => $item->currency_code,
            'status' => $item->status->value,
            'schedule' => $item->schedule?->toFrontendArray(),
            'nextPaymentDate' => $item->nextPaymentDate()?->toDateString(),
            'notes' => $item->notes,
        ];
    }
}
