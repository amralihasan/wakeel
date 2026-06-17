<?php

namespace App\Models;

use App\Concerns\BelongsToCompany;
use App\Enums\UnitStatus;
use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'rooms',
        'area',
        'price',
        'location',
        'down_payment',
        'installment_years',
        'status',
        'delivery_date',
    ];

    protected function casts(): array
    {
        return [
            'status' => UnitStatus::class,
        ];
    }

    public function media(): HasMany
    {
        return $this->hasMany(UnitMedia::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }
}
