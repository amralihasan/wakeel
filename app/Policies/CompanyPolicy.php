<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function view(User $user, Company $company): bool
    {
        return $user->company_id === $company->id;
    }

    public function update(User $user, Company $company): bool
    {
        return $user->company_id === $company->id && $user->isOwner();
    }

    public function manageTeam(User $user, Company $company): bool
    {
        return $user->company_id === $company->id && $user->isOwner();
    }

    public function manageBilling(User $user, Company $company): bool
    {
        return $user->company_id === $company->id && $user->isOwner();
    }

    public function manageUnits(User $user, Company $company): bool
    {
        return $user->company_id === $company->id && $user->isOwner();
    }

    public function manageBotSettings(User $user, Company $company): bool
    {
        return $user->company_id === $company->id && $user->isOwner();
    }
}
