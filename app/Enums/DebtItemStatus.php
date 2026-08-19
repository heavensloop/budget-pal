<?php

namespace App\Enums;

enum DebtItemStatus: string
{
    case Pending = 'pending';
    case Archived = 'archived';
}
