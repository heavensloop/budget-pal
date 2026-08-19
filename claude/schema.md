# Schema: full domain model

`budget_months` stays as the materialized per-month container (see
`schema.mermaid` / `schema-erd.png`) for domains that use it. Every
recurring-capable domain item gets a nullable `schedule_id` instead of
`is_recurring` / `recurring_group_id` / `recurrence_months_remaining` /
`reminder_day`.

**Needs is the exception**: auto-generating a `BudgetMonth` per page visit
caused enough issues that `needs_items` was decoupled from `BudgetMonth`
entirely (no `budget_month_id`, no per-month carry-forward row) — see the
Needs section below. Wants/Debts/Savings/Incoming/Additional are all
still unbuilt and still designed against the original per-month
`budget_month_id` pattern described here; that design hasn't been
revisited for them yet.

`BudgetItem` is a plain DTO (for now), not a persisted table — see the
bottom of this doc.

## Schedule (new table)

**Not polymorphic** — no `schedulable_type`/`schedulable_id`. A single
Schedule row is referenced by *many* item rows (one per month it
recurred), so there's no single "owner" to point back to; the working
link is entirely `needs_items.schedule_id -> schedules.id` (and later
`wants_items.schedule_id`, `debts.schedule_id`, etc.), the same shared-
lookup-table pattern as `categories`. An earlier draft of this doc had
`schedulable_type`/`schedulable_id` here, copied from schema.md's original
polymorphic sketch without adjusting it for the "layer on top of
budget_months" model — caught by the first test run (NOT NULL violation
creating a Schedule with no single owner to assign).

- `is_active` (boolean) — turn off to stop future carry-forward
- `recurrence` (nullable string: "monthly", "weekly", "biweekly", "yearly")
- `start_date` (nullable)
- `end_date` (nullable)
- `due_day` (nullable, 1-31)
- `reminder_days_before` (nullable int)

Every month's materialized row for the same recurring item shares the same
`schedule_id` — that's the "same series" identifier across months.
`GenerateNextBudgetMonth` finds rows in the latest month with a
`schedule_id` set and `schedule.is_active`, copies each into the new month
with the same `schedule_id`, amount carried forward, status reset to
pending. **No longer true for Needs**: a recurring Need is now a single
permanent `needs_items` row (no monthly duplication) with its next due
date computed on the fly from `schedule.due_day` — see `NeedsItem::nextPaymentDate()`.
`GenerateNextBudgetMonth` is kept only as a plain `BudgetMonth::firstOrCreate`,
not called anywhere yet.

## Categories (unchanged)

- `user_id` (nullable = system default), `name`, `type` (need/want/both),
  `color`, `icon`

## BudgetMonth (unchanged)

- `user_id`, `year`, `month`, `wants_budget_cap`

## Needs (`needs_items`)

**Not month-scoped** — no `budget_month_id`. A Need is a flat, permanent
row (recurring or one-time) that exists independent of any calendar
month; there's no month navigation on the Needs page and nothing
auto-generates a `BudgetMonth` for it.

- `user_id`, `category_id` (all FK, not null)
- `schedule_id` (nullable FK) — null means one-time
- `name`, `amount`, `currency_code`
- `status`: `pending`/`done`/`skipped`/`archived` — its own
  `NeedsItemStatus` enum, distinct from the shared `ItemStatus` enum other
  domains use, since "archived" is Needs-specific
- `date_due` (nullable date) — one-time items with a specific date;
  recurring items get their due date from `schedule.due_day`, computed
  fresh each time via `NeedsItem::nextPaymentDate()` (next upcoming
  occurrence from today, rolling into next month once this month's has
  passed)
- `notes`

Archived Needs are excluded from the default index listing; a
`show_archived=1` query param includes them in a separate section.

## Wants (`wants_items`)

Same shape as Needs, plus:

- `debt_id` (nullable FK -> `debts.id`) — set when this instance is a
  loan-repayment installment

## Debts

- `user_id`, `name`, `lender`, `original_amount`, `balance_remaining`,
  `monthly_installment`
- `schedule_id` (nullable FK) — an active schedule drives
  `GenerateNextBudgetMonth` to auto-create/carry-forward this debt's
  `wants_items` installment row each month (amount defaults to
  `monthly_installment`, adjusted to the remaining balance on the final
  payment)
- `start_date`, `expected_payoff_date`, `status` (active/paid_off), `notes`

## Savings Goals (`savings_goals`)

- `user_id`, `name`, `target_amount` (nullable), `target_date` (nullable)
- `schedule_id` (nullable FK) — replaces `is_recurring` /
  `recurrence_months_remaining`
- `status` (active/completed/archived)

## Savings Contributions (`savings_contributions`)

Materialized per-month row, generated from the goal's schedule — same
pattern as Needs/Wants:

- `budget_month_id`, `savings_goal_id` (FK, not null)
- `amount`, `status`, `notes`

## Incoming Items (`incoming_items`)

- `budget_month_id`, `user_id` (FK, not null)
- `schedule_id` (nullable FK) — replaces `is_recurring` /
  `recurring_group_id`
- `source_name`, `type` (salary/gift/loan_repayment/investment_maturity/other)
- `amount`, `currency_code`, `expected_date`
- `status` (pending/done/skipped) — same enum as Needs/Wants/Savings, for a
  uniform source-table shape feeding `budget_items` directly
- `notes`

## Additional Items (`additional_items`)

Unchanged — one-time by nature, no schedule:

- `budget_month_id`, `user_id` (FK, not null)
- `name`, `amount`, `type` (additional/unplanned_budget), `status`, `notes`

## BudgetItem (DTO, not a table — for now)

Originally a persisted, per-month, cross-domain projection table kept in
sync by a `SyncBudgetItem` Action on every source-row mutation. Retired:
`budget_items` had zero readers anywhere in the app, and once Needs (its
only writer) decoupled from `BudgetMonth`, the sync mechanism no longer
had a month to key off. `app/Models/BudgetItem.php`, the `budget_items`
migration, and `SyncBudgetItem` are all deleted.

`App\DataTransferObjects\BudgetItem` is a plain `final readonly class`
(`type`, `sourceId`, `name`, `amount`, `currencyCode`, `status`, `dateDue`,
`message`) — the same shape minus persistence plumbing (`id`,
`budget_month_id`, timestamps). Nothing constructs one yet; it exists as
the shape a future unified dashboard/Review pass can build against
on-demand from each domain's own rows, rather than a table that has to be
kept in sync. How that future pass actually assembles a unified list
across domains (query each table and merge? something else?) hasn't been
decided.
