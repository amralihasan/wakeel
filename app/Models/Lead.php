<?php

namespace App\Models;

use App\Concerns\BelongsToCompany;
use App\Enums\LeadTier;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'customer_phone',
        'name',
        'budget_max',
        'preferred_rooms',
        'preferred_location',
        'interested_unit_id',
        'score',
        'tier',
        'status',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'tier' => LeadTier::class,
        ];
    }

    public function interestedUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'interested_unit_id');
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function handoffs(): HasMany
    {
        return $this->hasMany(Handoff::class);
    }
}
