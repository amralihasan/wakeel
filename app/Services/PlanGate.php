<?php

namespace App\Services;

use App\Models\Company;

class PlanGate
{
    public function __construct(
        protected PlanCatalog $plans,
    ) {}

    public function withinLimit(Company $company, string $type, int $intended = 1): bool
    {
        $subscription = $company->subscription;

        if (! $subscription) {
            return false;
        }

        $limit = $this->plans->limit($subscription->plan_key, $type);

        if ($limit === null) {
            return true;
        }

        $current = match ($type) {
            'units' => $company->units()->count(),
            'reps' => $company->users()->where('role', 'sales_rep')->count(),
            'numbers' => filled($company->whatsapp_number) ? 1 : 0,
            default => 0,
        };

        return ($current + $intended) <= $limit;
    }

    public function remaining(Company $company, string $type): int
    {
        $subscription = $company->subscription;

        if (! $subscription) {
            return 0;
        }

        $limit = $this->plans->limit($subscription->plan_key, $type);

        if ($limit === null) {
            return PHP_INT_MAX;
        }

        $current = match ($type) {
            'units' => $company->units()->count(),
            'reps' => $company->users()->where('role', 'sales_rep')->count(),
            'numbers' => filled($company->whatsapp_number) ? 1 : 0,
            default => 0,
        };

        return max(0, $limit - $current);
    }
}
