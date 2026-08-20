<?php

namespace App\Enums;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

enum IncomeCategory: string implements ReadableEnumInterface
{
    use ReadableEnumTrait;

    #[EnumCase('Salary')]
    case Salary = 'salary';

    #[EnumCase('Freelance')]
    case Freelance = 'freelance';

    #[EnumCase('Business')]
    case Business = 'business';

    #[EnumCase('Gift')]
    case Gift = 'gift';

    #[EnumCase('Allowance')]
    case Allowance = 'allowance';

    #[EnumCase('Royalty')]
    case Royalty = 'royalty';

    #[EnumCase('Investment')]
    case Investment = 'investment';

    #[EnumCase('Other')]
    case Other = 'other';

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
