<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Conversation;

class ConversationUsage
{
    public function __construct(
        protected PlanCatalog $plans,
    ) {}

    public function quotaUsed(Company $company): int
    {
        return $company->conversations_count;
    }

    public function quotaRemaining(Company $company): int
    {
        $subscription = $company->subscription;

        if (! $subscription) {
            return 0;
        }

        $limit = $this->plans->limit($subscription->plan_key, 'conversation_quota');

        if ($limit === null) {
            return PHP_INT_MAX;
        }

        return max(0, $limit - $company->conversations_count);
    }

    public function isOverQuota(Company $company): bool
    {
        return $this->quotaRemaining($company) <= 0;
    }

    public function increment(Company $company, Conversation $conversation): void
    {
        $cycleStart = $company->billing_cycle_start;

        if ($cycleStart && $conversation->last_billable_cycle_start?->toDateTimeString() === $cycleStart->toDateTimeString()) {
            return;
        }

        $company->increment('conversations_count');
        $conversation->update(['last_billable_cycle_start' => $cycleStart ?? now()]);
    }

    public function resetForPeriod(Company $company): void
    {
        $company->update(['conversations_count' => 0]);
    }
}
