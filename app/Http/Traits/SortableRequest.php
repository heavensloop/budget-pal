<?php

namespace App\Http\Traits;

use App\Enums\SortDirection;
use Illuminate\Support\Stringable;

/**
 * Trait SortableRequest
 *
 * Provides sorting functionality for HTTP requests.
 *
 * @method Stringable string(string $key, mixed $default = null)
 * @method int Stringable(string $key, mixed $default = null)
 */
trait SortableRequest
{
    public function getSortField(?string $default = null): ?string
    {
        $field = $this->string('sort', $default)->toString();

        if (! $this->isValidSortField($field)) {
            return $default;
        }

        return $field;
    }

    public function getSortDirection(): SortDirection
    {
        $direction = $this->string('direction')->toString() === 'desc' ? 'desc' : 'asc';

        return SortDirection::from(strtolower($direction));
    }

    private function isValidSortField(?string $field): bool
    {
        return in_array($field, $this->getSortableFields(), true);
    }

    /** @return array<string> */
    abstract protected function getSortableFields(): array;
}
