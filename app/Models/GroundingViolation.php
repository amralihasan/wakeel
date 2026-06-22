<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroundingViolation extends Model
{
    protected $fillable = [
        'company_id',
        'conversation_id',
        'model_used',
        'original_text',
        'safe_fallback_text',
        'action_taken',
        'violation_reason',
        'retrieved_facts',
    ];

    protected function casts(): array
    {
        return [
            'retrieved_facts' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
