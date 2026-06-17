<?php

namespace App\Enums;

enum HandoffStatus: string
{
    case Waiting = 'waiting';
    case Active = 'active';
    case Resolved = 'resolved';
}
