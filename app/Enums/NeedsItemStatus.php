<?php

namespace App\Enums;

enum NeedsItemStatus: string
{
    case Pending = 'pending';
    case Done = 'done';
    case Skipped = 'skipped';
    case Archived = 'archived';
}
