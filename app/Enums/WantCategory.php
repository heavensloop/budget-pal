<?php

namespace App\Enums;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

enum WantCategory: string implements ReadableEnumInterface
{
    use ReadableEnumTrait;

    #[EnumCase('Electronics')]
    case ELECTRONICS = 'electronics';

    #[EnumCase('Clothing')]
    case CLOTHING = 'clothing';

    #[EnumCase('Entertainment')]
    case ENTERTAINMENT = 'entertainment';

    #[EnumCase('Gifts')]
    case GIFTS = 'gifts';

    #[EnumCase('Hobbies')]
    case HOBBIES = 'hobbies';

    #[EnumCase('Travel')]
    case TRAVEL = 'travel';

    #[EnumCase('Food and Dining')]
    case FOOD_AND_DINING = 'food_and_dining';

    #[EnumCase('Health and Fitness')]
    case HEALTH_AND_FITNESS = 'health_and_fitness';

    #[EnumCase('Other')]
    case OTHER = 'other';

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
