<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Events\SubscriptionActivated;
use App\Events\SubscriptionCanceled;
use App\Events\SubscriptionExpired;
use App\Events\SubscriptionPastDue;
use App\Services\PlanCatalog;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'plan_key',
        'status',
        'payment_method',
        'gateway',
        'gateway_token',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'next_charge_at',
        'last_payment_at',
        'grace_ends_at',
        'canceled_at',
        'cancel_at_period_end',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'next_charge_at' => 'datetime',
            'last_payment_at' => 'datetime',
            'grace_ends_at' => 'datetime',
            'canceled_at' => 'datetime',
            'cancel_at_period_end' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function onTrial(): bool
    {
        return $this->status === SubscriptionStatus::Trialing
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    public function isPastDue(): bool
    {
        return $this->status === SubscriptionStatus::PastDue;
    }

    public function onGrace(): bool
    {
        return $this->grace_ends_at !== null && $this->grace_ends_at->isFuture();
    }

    public function hasEnded(): bool
    {
        return $this->status->isTerminal();
    }

    public function daysUntilRenewal(): ?int
    {
        if (! $this->current_period_end) {
            return null;
        }

        return max(0, (int) now()->diffInDays($this->current_period_end, false));
    }

    public function plan(): ?array
    {
        return app(PlanCatalog::class)->find($this->plan_key);
    }

    public function transitionTo(SubscriptionStatus $newStatus): void
    {
        if (! $this->status->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$this->status->value} to {$newStatus->value}."
            );
        }

        $oldStatus = $this->status;
        $this->status = $newStatus;

        if ($newStatus === SubscriptionStatus::Canceled) {
            $this->canceled_at = now();
        }

        $this->save();

        match ($newStatus) {
            SubscriptionStatus::Active => SubscriptionActivated::dispatch($this, $oldStatus),
            SubscriptionStatus::PastDue => SubscriptionPastDue::dispatch($this, $oldStatus),
            SubscriptionStatus::Canceled => SubscriptionCanceled::dispatch($this, $oldStatus),
            SubscriptionStatus::Expired => SubscriptionExpired::dispatch($this, $oldStatus),
            SubscriptionStatus::Trialing => null,
        };
    }
}
