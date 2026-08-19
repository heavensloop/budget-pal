<?php

namespace App\Enums;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

enum MonthlyRecurrence: string implements ReadableEnumInterface
{
    use ReadableEnumTrait;

    #[EnumCase('Monthly')]
    case Monthly = 'monthly';

    #[EnumCase('Every few months')]
    case EveryNMonths = 'every_n_months';

    #[EnumCase('On specific months')]
    case SpecificMonths = 'specific_months';

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
