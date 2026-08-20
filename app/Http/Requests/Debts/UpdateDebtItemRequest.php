<?php

namespace App\Http\Requests\Debts;

use App\Enums\LoanCategory;
use App\Enums\MonthlyRecurrence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDebtItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('debt'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(LoanCategory::class)],
            'name' => ['required', 'string', 'max:255'],
            'amount_borrowed' => ['required', 'numeric', 'min:0'],
            'total_repayment_amount' => ['required', 'numeric', 'min:0', 'gte:amount_borrowed'],
            'monthly_repayment_amount' => ['required', 'numeric', 'min:0'],
            'tenure_months' => ['required', 'integer', 'min:1'],
            'payments_made' => ['nullable', 'integer', 'min:0', 'lte:tenure_months'],
            'notes' => ['nullable', 'string'],
            'schedule' => ['required', 'array'],
            'schedule.recurrence' => ['nullable', Rule::enum(MonthlyRecurrence::class)],
            'schedule.start_date' => ['required', 'date'],
            'schedule.end_date' => ['nullable', 'date', 'after_or_equal:schedule.start_date'],
            'schedule.reminder_days_before' => ['nullable', 'integer', 'between:0,365'],
            'schedule.interval_months' => ['required_if:schedule.recurrence,every_n_months', 'integer', 'between:2,6'],
            'schedule.months' => ['required_if:schedule.recurrence,specific_months', 'array', 'min:1'],
            'schedule.months.*' => ['integer', 'between:1,12', 'distinct'],
        ];
    }
}
