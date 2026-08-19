<?php

namespace App\Http\Requests\Debts;

use App\Enums\LoanCategory;
use App\Enums\MonthlyRecurrence;
use App\Models\DebtItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDebtItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', DebtItem::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'category' => ['required', Rule::enum(LoanCategory::class)],
            'name' => ['required', 'string', 'max:255'],
            'principal' => ['required', 'numeric', 'min:0'],
            'balance' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'schedule' => ['nullable', 'array'],
            'schedule.recurrence' => ['nullable', Rule::enum(MonthlyRecurrence::class)],
            'schedule.start_date' => ['required_with:schedule', 'date'],
            'schedule.end_date' => ['nullable', 'date', 'after_or_equal:schedule.start_date'],
            'schedule.reminder_days_before' => ['nullable', 'integer', 'between:0,365'],
            'schedule.interval_months' => ['required_if:schedule.recurrence,every_n_months', 'integer', 'between:2,6'],
            'schedule.months' => ['required_if:schedule.recurrence,specific_months', 'array', 'min:1'],
            'schedule.months.*' => ['integer', 'between:1,12', 'distinct'],
        ];

        if (empty($this->input('schedule'))) {
            $rules['name'][] = Rule::unique('debt_items')
                ->where(fn ($query) => $query->where('user_id', $this->user()->id)->whereNull('schedule_id'));
        }

        return $rules;
    }
}
