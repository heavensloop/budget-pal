<?php

namespace App\Http\Controllers\Web;

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

        $currentMonth = [
            'year' => $year,
            'month' => $month,
            'label' => Carbon::createFromDate($year, $month, 1)->format('F Y'),
        ];

        $budgetMonth = BudgetMonth::query()
            ->where('user_id', $request->user()->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if (! $budgetMonth) {
            return Inertia::render('Dashboard', [
                'currentMonth' => $currentMonth,
                'hasBudgetMonth' => false,
            ]);
        }

        return Inertia::render('Dashboard', [
            'currentMonth' => $currentMonth,
            'hasBudgetMonth' => true,
            'kpis' => [
                'income' => ['amount' => 0, 'currencyCode' => 'NGN'],
                'needs' => [
                    'amount' => 0,
                    'currencyCode' => 'NGN',
                    'paidCount' => 0,
                    'totalCount' => 0,
                ],
                'wants' => [
                    'spent' => 0,
                    'cap' => (float) $budgetMonth->wants_budget_cap,
                    'currencyCode' => 'NGN',
                ],
                'savings' => ['amount' => 0, 'currencyCode' => 'NGN', 'delta' => 0],
            ],
            'needsByCategory' => [],
            'recentItems' => [],
            'reminders' => [],
        ]);
    }
}
