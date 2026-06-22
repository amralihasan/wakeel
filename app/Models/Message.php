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
        'company_id',
        'conversation_id',
        'direction',
        'sender',
        'body',
        'media_url',
        'media_type',
        'wa_message_id',
        'created_at',
        'input_tokens',
        'output_tokens',
        'model_used',
        'cost_usd',
    ];

    protected function casts(): array
    {
        return [
            'direction' => MessageDirection::class,
            'sender' => MessageSender::class,
            'created_at' => 'datetime',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'cost_usd' => 'float',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    protected static function booted(): void
    {
        static::created(function (Message $message) {
            $message->conversation()->update([
                'last_message_at' => $message->created_at ?? now(),
            ]);

            if ($message->direction === MessageDirection::Outbound) {
                $conversation = $message->conversation;
                $company = $conversation->company;
                $company->ensureCurrentBillingCycle();

                if ($conversation->last_billable_cycle_start?->toDateTimeString() !== $company->billing_cycle_start?->toDateTimeString()) {
                    $company->increment('conversations_count');
                    $conversation->update(['last_billable_cycle_start' => $company->billing_cycle_start]);
                }
            }
        });
    }
}
