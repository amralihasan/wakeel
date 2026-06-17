<?php

namespace App\Enums;

enum MessageSender: string
{
    case Customer = 'customer';
    case Bot = 'bot';
    case Rep = 'rep';
}
