<?php

namespace App\Http\Requests\Needs;

use App\Http\Traits\SortableRequest;
use App\Models\NeedsItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ShowNeedsRequest extends FormRequest
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
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'sort' => ['nullable', 'string'],
            'direction' => ['nullable', 'string'],
        ];
    }

    public function getYear(): int
    {
        return $this->integer('year', now()->year);
    }

    public function getMonth(): int
    {
        return $this->integer('month', now()->month);
    }

    /**
     * Get the list of sortable columns.
     *
     * @return array<int, string>
     */
    protected function getSortableFields(): array
    {
        return NeedsItem::SORTABLE_COLUMNS;
    }
}
