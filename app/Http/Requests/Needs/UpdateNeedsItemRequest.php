<?php

namespace App\Http\Requests\Needs;

use App\Enums\RecurrenceFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNeedsItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('need'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'schedule' => ['nullable', 'array'],
            'schedule.recurrence' => ['nullable', Rule::enum(RecurrenceFrequency::class)],
            'schedule.start_date' => ['required_with:schedule', 'date'],
            'schedule.end_date' => ['nullable', 'date', 'after_or_equal:schedule.start_date'],
            'schedule.reminder_days_before' => ['nullable', 'integer', 'between:0,365'],
        ];

        if (empty($this->input('schedule'))) {
            $rules['name'][] = Rule::unique('needs_items')
                ->where(fn ($query) => $query->where('user_id', $this->user()->id)->whereNull('schedule_id'))
                ->ignore($this->route('need'));
        }

        return $rules;
    }
}
