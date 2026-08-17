<?php

namespace App\Enums;

enum ItemStatus: string
{
    case Pending = 'pending';
    case Done = 'done';
    case Skipped = 'skipped';
}
