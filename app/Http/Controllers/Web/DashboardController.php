<?php

namespace App\Http\Controllers\Web;

use App\Enums\ItemStatus;
use App\Http\Controllers\Controller;
use App\Models\BudgetMonth;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $year = $request->integer('year', now()->year);
        $month = $request->integer('month', now()->month);

        $budgetMonth = BudgetMonth::firstOrCreate([
            'user_id' => $request->user()->id,
            'year' => $year,
            'month' => $month,
        ]);

        $needsItems = $budgetMonth->needsItems()->with('category')->latest()->get();

        $needsByCategory = $needsItems
            ->groupBy(fn ($item) => $item->category->name)
            ->map(fn ($items, $name) => ['label' => $name, 'amount' => (float) $items->sum('amount')])
            ->sortByDesc('amount')
            ->values();

        $recentItems = $needsItems->take(7)->map(fn ($item) => [
            'name' => $item->name,
            'category' => $item->category->name,
            'amount' => (float) $item->amount,
            'currencyCode' => $item->currency_code,
            'status' => $item->status->value,
        ])->values();

        return Inertia::render('Dashboard', [
            'currentMonth' => [
                'year' => $year,
                'month' => $month,
                'label' => Carbon::createFromDate($year, $month, 1)->format('F Y'),
            ],
            'kpis' => [
                'income' => ['amount' => 0, 'currencyCode' => 'NGN'],
                'needs' => [
                    'amount' => (float) $needsItems->sum('amount'),
                    'currencyCode' => 'NGN',
                    'paidCount' => $needsItems->where('status', ItemStatus::Done)->count(),
                    'totalCount' => $needsItems->count(),
                ],
                'wants' => [
                    'spent' => 0,
                    'cap' => (float) $budgetMonth->wants_budget_cap,
                    'currencyCode' => 'NGN',
                ],
                'savings' => ['amount' => 0, 'currencyCode' => 'NGN', 'delta' => 0],
            ],
            'needsByCategory' => $needsByCategory,
            'recentItems' => $recentItems,
            'reminders' => [],
        ]);
    }
}
