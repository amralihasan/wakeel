<?php

namespace App\Concerns;

use App\Models\Company;
use App\Models\Scopes\CompanyScope;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model) {
            if (Auth::check() && ! $model->company_id) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
