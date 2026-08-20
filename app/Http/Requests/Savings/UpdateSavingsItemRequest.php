<?php

namespace App\Http\Requests\Savings;

use App\Enums\MonthlyRecurrence;
use App\Enums\SavingsItemStatus;
use App\Enums\SavingsItemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSavingsItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('saving'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(SavingsItemType::class)],
            'name' => ['required', 'string', 'max:255'],
            'target_amount' => ['required', 'numeric', 'min:0'],
            'installment_amount' => ['required', 'numeric', 'min:0'],
            'target_profit' => ['nullable', 'numeric', 'min:0'],
            'maturity_date' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(SavingsItemStatus::class)],
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
