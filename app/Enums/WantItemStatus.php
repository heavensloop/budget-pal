<?php

namespace App\Enums;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

enum WantItemStatus: string implements ReadableEnumInterface
{
    use ReadableEnumTrait;

    #[EnumCase('Planned')]
    case PLANNED = 'planned';

    #[EnumCase('Purchased')]
    case PURCHASED = 'purchased';

    #[EnumCase('Archived')]
    case ARCHIVED = 'archived';

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
