<?php

namespace Tests\Unit\Actions\Budget;

use App\Actions\Budget\GenerateNextBudgetMonth;
use App\Models\BudgetMonth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateNextBudgetMonthTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_the_target_month()
    {
        $user = User::factory()->create();

        $budgetMonth = app(GenerateNextBudgetMonth::class)($user, 2026, 3);

        $this->assertSame(2026, $budgetMonth->year);
        $this->assertSame(3, $budgetMonth->month);
    }

    public function test_is_idempotent_when_run_twice_for_the_same_month()
    {
        $user = User::factory()->create();

        $first = app(GenerateNextBudgetMonth::class)($user, 2026, 3);
        $second = app(GenerateNextBudgetMonth::class)($user, 2026, 3);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, BudgetMonth::count());
    }
}
