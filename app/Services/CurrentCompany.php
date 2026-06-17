<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Auth;

class CurrentCompany
{
    protected ?Company $company = null;

    public function get(): ?Company
    {
        if (! $this->company && Auth::check()) {
            $this->company = Auth::user()->company;
        }

        return $this->company;
    }

    public function set(Company $company): void
    {
        $this->company = $company;
    }
}
