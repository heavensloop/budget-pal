<?php

namespace App\Enums;

enum IncomeItemStatus: string
{
    case Pending = 'pending';
    case Archived = 'archived';
}
