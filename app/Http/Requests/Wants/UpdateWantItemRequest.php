<?php

namespace App\Http\Requests\Wants;

use App\Enums\WantCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWantItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('want'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(WantCategory::class)],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'schedule' => ['nullable', 'array'],
            'schedule.start_date' => ['required_with:schedule', 'date'],
            'schedule.reminder_days_before' => ['nullable', 'integer', 'between:0,365'],
        ];
    }
}
