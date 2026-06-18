<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    protected $fillable = [
        'name',
        'company',
        'email',
        'phone',
        'message',
        'locale',
        'ip_address',
        'is_handled',
        'handled_by',
        'handled_at',
        'assigned_to',
    ];

    protected function casts(): array
    {
        return [
            'is_handled' => 'boolean',
            'handled_at' => 'datetime',
        ];
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
