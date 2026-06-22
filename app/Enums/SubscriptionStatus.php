<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case Canceled = 'canceled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Trialing => __('billing.status_trialing'),
            self::Active => __('billing.status_active'),
            self::PastDue => __('billing.status_past_due'),
            self::Canceled => __('billing.status_canceled'),
            self::Expired => __('billing.status_expired'),
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Trialing => [self::Active, self::Canceled, self::Expired],
            self::Active => [self::PastDue, self::Canceled],
            self::PastDue => [self::Active, self::Canceled, self::Expired],
            self::Canceled => [],
            self::Expired => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this === self::Canceled || $this === self::Expired;
    }

    public function isActive(): bool
    {
        return $this === self::Active || $this === self::Trialing;
    }
}
