<?php

namespace App\Models;

use App\Services\PlanCatalog;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'default_locale',
        'slug',
        'email',
        'phone',
        'plan',
        'whatsapp_number',
        'dialog360_channel_id',
        'bot_settings',
        'is_active',
        'onboarding_completed',
        'billing_cycle_start',
        'billing_cycle_end',
        'conversations_count',
        'paymob_subscription_id',
        'trial_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'bot_settings' => 'array',
            'is_active' => 'boolean',
            'onboarding_completed' => 'boolean',
            'billing_cycle_start' => 'datetime',
            'billing_cycle_end' => 'datetime',
            'trial_ends_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function getPlanDetails(): array
    {
        $catalog = app(PlanCatalog::class);

        if ($this->subscription) {
            $plan = $catalog->find($this->subscription->plan_key);

            if ($plan) {
                return $plan;
            }
        }

        return $catalog->find($this->plan) ?? $catalog->find('starter') ?? [];
    }

    public function hasReachedConversationsLimit(): bool
    {
        $plan = $this->getPlanDetails();
        $limit = $plan['limits']['conversation_quota'] ?? null;

        return $limit !== null && $this->conversations_count >= $limit;
    }

    public function hasReachedUnitsLimit(): bool
    {
        $plan = $this->getPlanDetails();
        $limit = $plan['limits']['units'] ?? null;

        return $limit !== null && $this->units()->count() >= $limit;
    }

    public function hasReachedRepsLimit(): bool
    {
        $plan = $this->getPlanDetails();
        $limit = $plan['limits']['reps'] ?? null;

        return $limit !== null && $this->users()->where('role', 'sales_rep')->count() >= $limit;
    }

    public function hasReachedNumbersLimit(): bool
    {
        $plan = $this->getPlanDetails();
        $limit = $plan['limits']['numbers'] ?? null;

        return $limit !== null && filled($this->whatsapp_number);
    }

    public function ensureCurrentBillingCycle(): void
    {
        if ($this->billing_cycle_start === null || $this->billing_cycle_end === null) {
            $now = now();
            $this->billing_cycle_start = $now;
            $this->billing_cycle_end = $now->copy()->addMonth();
            $this->conversations_count = 0;
            $this->save();
        }
    }

    public function rolloverBillingCycle(): void
    {
        $now = now();
        $this->billing_cycle_start = $now;
        $this->billing_cycle_end = $now->copy()->addMonth();
        $this->conversations_count = 0;
        $this->save();
    }

    public function hasPaymobSubscription(): bool
    {
        return filled($this->paymob_subscription_id);
    }
}
