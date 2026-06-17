<?php

namespace App\Models;

use App\Concerns\BelongsToCompany;
use App\Enums\ConversationMode;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'customer_phone',
        'lead_id',
        'mode',
        'assigned_rep_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'mode' => ConversationMode::class,
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_rep_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function handoffs(): HasMany
    {
        return $this->hasMany(Handoff::class);
    }
}
