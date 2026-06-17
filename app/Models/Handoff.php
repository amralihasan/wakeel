<?php

namespace App\Models;

use App\Concerns\BelongsToCompany;
use App\Enums\HandoffStatus;
use Database\Factories\HandoffFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Handoff extends Model
{
    /** @use HasFactory<HandoffFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'conversation_id',
        'lead_id',
        'reason',
        'ai_summary',
        'status',
        'agent_id',
        'claimed_at',
        'resolved_at',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => HandoffStatus::class,
            'claimed_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
