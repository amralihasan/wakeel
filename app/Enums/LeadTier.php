<?php

namespace App\Enums;

enum LeadTier: string
{
    case Hot = 'hot';
    case Warm = 'warm';
    case Cold = 'cold';
}
