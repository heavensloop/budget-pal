<?php

namespace App\Http\Controllers\Web;

use App\Actions\Budget\CreateNeedsItem;
use App\Actions\Budget\GenerateNextBudgetMonth;
use App\Actions\Budget\MarkItemStatus;
use App\Actions\Budget\UpdateNeedsItem;
use App\Enums\CategoryType;
use App\Enums\ItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Needs\StoreNeedsItemRequest;
use App\Http\Requests\Needs\UpdateNeedsItemRequest;
use App\Models\Category;
use App\Models\NeedsItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class NeedsController extends Controller
{
    public function __construct(private readonly GenerateNextBudgetMonth $generateNextBudgetMonth) {}

    public function index(Request $request): Response
    {
        $year = $request->integer('year', now()->year);
        $month = $request->integer('month', now()->month);

        $budgetMonth = ($this->generateNextBudgetMonth)($request->user(), $year, $month);

        $items = $budgetMonth->needsItems()
            ->with(['category', 'schedule'])
            ->latest()
            ->get()
            ->map(fn (NeedsItem $item) => [
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
                'notes' => $item->notes,
            ]);

        return Inertia::render('Needs/Index', [
            'currentMonth' => [
                'year' => $year,
                'month' => $month,
                'label' => Carbon::createFromDate($year, $month, 1)->format('F Y'),
            ],
            'categories' => Category::query()
                ->whereIn('type', [CategoryType::Need, CategoryType::Both])
                ->orderBy('name')
                ->get(['id', 'name']),
            'items' => $items,
        ]);
    }

    public function store(StoreNeedsItemRequest $request, CreateNeedsItem $createNeedsItem): RedirectResponse
    {
        $year = $request->integer('year', now()->year);
        $month = $request->integer('month', now()->month);

        $budgetMonth = ($this->generateNextBudgetMonth)($request->user(), $year, $month);

        $createNeedsItem($budgetMonth, $request->user(), $request->validated());

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

        $need->budgetItem?->delete();
        $need->delete();

        return back();
    }

    public function updateStatus(Request $request, NeedsItem $need, MarkItemStatus $markItemStatus): RedirectResponse
    {
        Gate::authorize('update', $need);

        $status = ItemStatus::from($request->string('status')->toString());

        $markItemStatus($need, $status);

        return back();
    }
}
