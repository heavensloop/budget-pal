<?php

namespace App\Enums;

enum RecurrenceFrequency: string
{
    case Monthly = 'monthly';
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Yearly = 'yearly';
}
