<?php

namespace Tests\Feature;

use App\Models\BudgetMonth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_shows_an_empty_state_when_no_budget_month_exists()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard', ['year' => 2026, 'month' => 3]));

        $response->assertInertia(fn ($page) => $page
            ->where('hasBudgetMonth', false)
            ->missing('kpis'),
        );
    }

    public function test_renders_kpi_cards_when_a_budget_month_exists()
    {
        $user = User::factory()->create();
        BudgetMonth::factory()->create(['user_id' => $user->id, 'year' => 2026, 'month' => 3]);

        $response = $this->actingAs($user)->get(route('dashboard', ['year' => 2026, 'month' => 3]));

        $response->assertInertia(fn ($page) => $page->where('hasBudgetMonth', true));
    }
}
