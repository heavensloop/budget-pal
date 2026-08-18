# Schema: full domain model

`budget_months` stays as the materialized per-month container (see
`schema.mermaid` / `schema-erd.png`). Every recurring-capable domain item
gets a nullable `schedule_id` instead of `is_recurring` /
`recurring_group_id` / `recurrence_months_remaining` / `reminder_day`.
`budget_items` is a real table (not just a DTO) — a per-month, cross-domain
projection kept in sync by the Actions that mutate the source rows.

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
pending.

## Categories (unchanged)

- `user_id` (nullable = system default), `name`, `type` (need/want/both),
  `color`, `icon`

## BudgetMonth (unchanged)

- `user_id`, `year`, `month`, `wants_budget_cap`

## Needs (`needs_items`)

- `budget_month_id`, `user_id`, `category_id` (all FK, not null)
- `schedule_id` (nullable FK) — null means one-time
- `name`, `amount`, `currency_code`, `status` (pending/done/skipped)
- `date_due` (nullable date) — one-time items with a specific date;
  recurring items get their due date from `schedule.due_day`
- `notes`

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

## BudgetItem (new table, not a DTO)

A per-month, cross-domain projection for fast unified querying (dashboard,
Review & Insights). Pure derived cache — **never edited directly**; the
source row is always authoritative.

- `budget_month_id` (FK, not null) — `BudgetMonth hasMany BudgetItems`
- `source_type`, `source_id` (polymorphic) — points back to the originating
  `needs_items` / `wants_items` / `savings_contributions` / `incoming_items`
  / `additional_items` row
- `type` (needs/wants/debts/savings/incoming/additional) — display category
- `name`, `amount`, `currency_code`
- `status` — mirrored from the source row
- `date_due` (nullable)
- `message` (nullable) — computed display text, e.g. "Due on the 1st of
  the month"

**Sync mechanism:** explicit, not a model observer. Every Action
that creates/updates/deletes a source row (`CreateNeedsItem`,
`UpdateNeedsItem`, `DeleteNeedsItem`, `MarkItemStatus`, ...) ends by calling
a shared `SyncBudgetItem` Action for that row. `GenerateNextBudgetMonth`
calls it once per row it generates. Marking something "done" from a
unified list always resolves back to the source row via `source_type`/
`source_id` and runs through the normal `MarkItemStatus` Action — never
writes `budget_items.status` directly. Debts don't get their own
`budget_items` row; a debt's monthly installment shows up via its linked
`wants_items` row instead.
