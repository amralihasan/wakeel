<?php

namespace App\Models;

use Database\Factories\UnitMediaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitMedia extends Model
{
    /** @use HasFactory<UnitMediaFactory> */
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'type',
        'path',
        'caption',
        'sort_order',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
