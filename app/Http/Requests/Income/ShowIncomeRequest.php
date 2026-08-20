<?php

namespace App\Http\Requests\Income;

use App\Http\Traits\SortableRequest;
use App\Models\IncomeItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ShowIncomeRequest extends FormRequest
{
    use SortableRequest;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sort' => ['nullable', 'string'],
            'direction' => ['nullable', 'string'],
            'show_archived' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get the list of sortable columns.
     *
     * @return array<int, string>
     */
    protected function getSortableFields(): array
    {
        return IncomeItem::SORTABLE_COLUMNS;
    }
}
