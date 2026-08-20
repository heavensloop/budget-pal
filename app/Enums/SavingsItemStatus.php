<?php

namespace App\Enums;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

enum SavingsItemStatus: string implements ReadableEnumInterface
{
    use ReadableEnumTrait;

    #[EnumCase('Pending')]
    case PENDING = 'pending';

    #[EnumCase('Ongoing')]
    case ONGOING = 'ongoing';

    #[EnumCase('Archived')]
    case ARCHIVED = 'archived';

    #[EnumCase('Completed')]
    case COMPLETED = 'completed';

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
