<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['number', 'channel_id', 'status', 'assigned_company_id'])]
class WhatsAppChannel extends Model
{
    protected $table = 'whatsapp_channels';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'assigned_company_id');
    }
}
