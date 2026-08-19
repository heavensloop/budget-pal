<?php

namespace App\DataTransferObjects;

use App\Enums\BudgetItemType;
use App\Enums\ItemStatus;
use Carbon\CarbonImmutable;

final readonly class BudgetItem
{
    public function __construct(
        public BudgetItemType $type,
        public int $sourceId,
        public string $name,
        public string $amount,
        public string $currencyCode,
        public ItemStatus $status,
        public ?CarbonImmutable $dateDue = null,
        public ?string $message = null,
    ) {}
}
