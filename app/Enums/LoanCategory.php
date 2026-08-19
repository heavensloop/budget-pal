<?php

namespace App\Enums;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

enum LoanCategory: string implements ReadableEnumInterface
{
    use ReadableEnumTrait;

    #[EnumCase('Auto Loan')]
    case Auto = 'auto';

    #[EnumCase('Personal Loan')]
    case Personal = 'personal';

    #[EnumCase('Business Loan')]
    case Business = 'business';

    #[EnumCase('Student Loan')]
    case Student = 'student';

    #[EnumCase('Credit Card')]
    case CreditCard = 'credit_card';

    #[EnumCase('Mortgage')]
    case Mortgage = 'mortgage';

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
