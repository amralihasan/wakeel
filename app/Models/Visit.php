<?php

namespace App\Models;

use App\Concerns\BelongsToCompany;
use App\Enums\VisitStatus;
use Database\Factories\VisitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visit extends Model
{
    /** @use HasFactory<VisitFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'lead_id',
        'unit_id',
        'scheduled_at',
        'status',
        'assigned_rep_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => VisitStatus::class,
            'scheduled_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function assignedRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_rep_id');
    }
}
