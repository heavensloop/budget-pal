<?php

namespace App\Enums;

enum CategoryType: string
{
    case Need = 'need';
    case Want = 'want';
    case Both = 'both';
}
