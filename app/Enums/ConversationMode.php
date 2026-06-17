<?php

namespace App\Enums;

enum ConversationMode: string
{
    case Bot = 'bot';
    case PendingHandoff = 'pending_handoff';
    case Human = 'human';
}
