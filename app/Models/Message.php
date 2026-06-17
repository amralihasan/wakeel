<?php

namespace App\Models;

use App\Concerns\BelongsToCompany;
use App\Enums\MessageDirection;
use App\Enums\MessageSender;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use BelongsToCompany, HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'direction',
        'sender',
        'body',
        'media_url',
        'media_type',
        'wa_message_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'direction' => MessageDirection::class,
            'sender' => MessageSender::class,
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
