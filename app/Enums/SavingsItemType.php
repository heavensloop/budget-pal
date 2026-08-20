<?php

namespace App\Enums;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

enum SavingsItemType: string implements ReadableEnumInterface
{
    use ReadableEnumTrait;

    #[EnumCase('Savings')]
    case SAVINGS = 'savings';

    #[EnumCase('Investment')]
    case INVESTMENT = 'investment';

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->getReadable()],
            self::cases(),
        );
    }
}
