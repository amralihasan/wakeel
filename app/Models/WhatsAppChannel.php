<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['number', 'channel_id', 'status', 'assigned_company_id', 'label', 'last_inbound_at', 'webhook_ok'])]
class WhatsAppChannel extends Model
{
    protected $table = 'whatsapp_channels';

    protected function casts(): array
    {
        return [
            'last_inbound_at' => 'datetime',
            'webhook_ok' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'assigned_company_id');
    }
}
