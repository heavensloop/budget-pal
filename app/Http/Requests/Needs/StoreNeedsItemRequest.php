<?php

namespace App\Http\Requests\Needs;

use App\Models\NeedsItem;
use Illuminate\Foundation\Http\FormRequest;

class StoreNeedsItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', NeedsItem::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'is_recurring' => ['sometimes', 'boolean'],
            'due_day' => ['nullable', 'integer', 'between:1,31'],
            'date_due' => ['nullable', 'date'],
        ];
    }
}
