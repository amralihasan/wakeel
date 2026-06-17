<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case SalesRep = 'sales_rep';
}
