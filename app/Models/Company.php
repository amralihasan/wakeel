<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'plan',
        'whatsapp_number',
        'dialog360_channel_id',
        'bot_settings',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'bot_settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
