<?php

namespace App\Http\Controllers\Web;

use App\Actions\Budget\CreateDebtItem;
use App\Actions\Budget\MarkDebtItemStatus;
use App\Actions\Budget\RecordDebtPayment;
use App\Actions\Budget\UpdateDebtItem;
use App\Enums\DebtItemStatus;
use App\Enums\LoanCategory;
use App\Enums\MonthlyRecurrence;
use App\Http\Controllers\Controller;
use App\Http\Requests\Debts\ShowDebtsRequest;
use App\Http\Requests\Debts\StoreDebtItemRequest;
use App\Http\Requests\Debts\UpdateDebtItemRequest;
use App\Models\DebtItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DebtsController extends Controller
{
    public function index(ShowDebtsRequest $request): Response
    {
        $sort = $request->getSortField('name');
        $direction = $request->getSortDirection();
        $showArchived = $request->boolean('show_archived');

        $query = DebtItem::query()
            ->where('user_id', $request->user()->id)
            ->with(['schedule'])
            ->sortable($sort, $direction);

        if (! $showArchived) {
            $query->where('status', '!=', DebtItemStatus::Archived);
        }

        $items = $query->get()->map($this->mapItem(...));

        return Inertia::render('Debts/Index', [
            'categoryOptions' => LoanCategory::options(),
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

    public function store(StoreDebtItemRequest $request, CreateDebtItem $createDebtItem): RedirectResponse
    {
        $createDebtItem($request->user(), $request->validated());

        return back();
    }

    public function update(UpdateDebtItemRequest $request, DebtItem $debt, UpdateDebtItem $updateDebtItem): RedirectResponse
    {
        $updateDebtItem($debt, $request->validated());

        return back();
    }

    public function destroy(DebtItem $debt): RedirectResponse
    {
        Gate::authorize('delete', $debt);

        $debt->delete();

        return back();
    }

    public function updateStatus(Request $request, DebtItem $debt, MarkDebtItemStatus $markDebtItemStatus): RedirectResponse
    {
        Gate::authorize('update', $debt);

        $status = DebtItemStatus::from($request->string('status')->toString());

        $markDebtItemStatus($debt, $status);

        return back();
    }

    public function recordPayment(DebtItem $debt, RecordDebtPayment $recordDebtPayment): RedirectResponse
    {
        Gate::authorize('update', $debt);

        if ($debt->payments_made >= $debt->tenure_months) {
            return back()->withErrors([
                'payment' => 'This debt has already been fully paid off.',
            ]);
        }

        if ($debt->hasPaidCurrentPeriod()) {
            return back()->withErrors([
                'payment' => 'A payment has already been recorded for this period.',
            ]);
        }

        $recordDebtPayment($debt);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapItem(DebtItem $item): array
    {
        return [
            'id' => $item->id,
            'category' => $item->category->value,
            'categoryLabel' => $item->category->getReadable(),
            'name' => $item->name,
            'amountBorrowed' => (float) $item->amount_borrowed,
            'totalRepaymentAmount' => (float) $item->total_repayment_amount,
            'monthlyRepaymentAmount' => (float) $item->monthly_repayment_amount,
            'tenureMonths' => $item->tenure_months,
            'paymentsMade' => $item->payments_made,
            'balance' => $item->balance(),
            'remainingTenure' => $item->remainingTenure(),
            'interestMonthly' => $item->interestMonthly(),
            'interestRate' => $item->interestRate(),
            'currencyCode' => $item->currency_code,
            'status' => $item->status->value,
            'lastPaymentDate' => $item->last_payment_date?->toDateString(),
            'schedule' => $item->schedule?->toFrontendArray(),
            'nextPaymentDate' => $item->nextPaymentDate()?->toDateString(),
            'canRecordPayment' => ! $item->hasPaidCurrentPeriod() && $item->payments_made < $item->tenure_months,
            'notes' => $item->notes,
        ];
    }
}
