<?php

namespace App\Enums;

enum BudgetItemType: string
{
    case Needs = 'needs';
    case Wants = 'wants';
    case Debts = 'debts';
    case Savings = 'savings';
    case Incoming = 'incoming';
    case Additional = 'additional';
}
