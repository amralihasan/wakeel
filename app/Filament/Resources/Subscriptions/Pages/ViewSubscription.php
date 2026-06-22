<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Enums\SubscriptionStatus;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Models\AdminAuditLog;
use App\Models\Subscription;
use App\Services\SubscriptionManager;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewSubscription extends ViewRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancel')
                ->label(__('admin.cancel_subscription'))
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn (Subscription $record) => $record->isActive())
                ->requiresConfirmation()
                ->modalDescription(__('admin.confirm_cancel_subscription'))
                ->action(function (Subscription $record) {
                    app(SubscriptionManager::class)->cancel($record, atPeriodEnd: true);
                    AdminAuditLog::record(
                        Auth::user(),
                        'subscription_canceled',
                        'subscription',
                        $record->id,
                        "Canceled subscription for {$record->company?->name}",
                    );
                    Notification::make()->success()->title(__('admin.subscription').' '.__('admin.canceled'))->send();
                    $this->refreshFormData(['status', 'cancel_at_period_end']);
                }),
            Action::make('cancel_immediate')
                ->label(__('admin.cancel_immediately'))
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn (Subscription $record) => $record->isActive())
                ->requiresConfirmation()
                ->modalDescription(__('admin.confirm_cancel_immediate'))
                ->action(function (Subscription $record) {
                    app(SubscriptionManager::class)->cancel($record, atPeriodEnd: false);
                    AdminAuditLog::record(
                        Auth::user(),
                        'subscription_canceled_immediate',
                        'subscription',
                        $record->id,
                        "Immediately canceled subscription for {$record->company?->name}",
                    );
                    Notification::make()->success()->title(__('admin.subscription').' '.__('admin.canceled'))->send();
                    $this->refreshFormData(['status', 'canceled_at', 'cancel_at_period_end']);
                }),
            Action::make('reactivate')
                ->label(__('admin.reactivate_subscription'))
                ->color('success')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (Subscription $record) => $record->cancel_at_period_end)
                ->requiresConfirmation()
                ->modalDescription(__('admin.confirm_reactivate'))
                ->action(function (Subscription $record) {
                    app(SubscriptionManager::class)->resume($record);
                    AdminAuditLog::record(
                        Auth::user(),
                        'subscription_reactivated',
                        'subscription',
                        $record->id,
                        "Reactivated subscription for {$record->company?->name}",
                    );
                    Notification::make()->success()->title(__('admin.reactivate_subscription'))->send();
                    $this->refreshFormData(['status', 'cancel_at_period_end', 'canceled_at']);
                }),
            Action::make('change_plan')
                ->label(__('admin.change_plan'))
                ->color('warning')
                ->icon('heroicon-o-arrow-right-arrow-left')
                ->visible(fn (Subscription $record) => $record->isActive())
                ->form([
                    Select::make('plan_key')
                        ->label(__('admin.plan'))
                        ->options([
                            'starter' => __('admin.starter'),
                            'growth' => __('admin.growth'),
                            'enterprise' => __('admin.enterprise'),
                        ])
                        ->required(),
                ])
                ->action(function (array $data, Subscription $record) {
                    app(SubscriptionManager::class)->changePlan($record, $data['plan_key']);
                    AdminAuditLog::record(
                        Auth::user(),
                        'plan_changed',
                        'subscription',
                        $record->id,
                        "Changed plan to {$data['plan_key']} for {$record->company?->name}",
                    );
                    Notification::make()->success()->title(__('admin.plan').' '.__('admin.changed'))->send();
                    $this->refreshFormData(['plan_key']);
                }),
            Action::make('mark_paid')
                ->label(__('admin.mark_paid'))
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn (Subscription $record) => $record->status === SubscriptionStatus::PastDue)
                ->requiresConfirmation()
                ->modalDescription(__('admin.confirm_mark_paid'))
                ->action(function (Subscription $record) {
                    $manager = app(SubscriptionManager::class);
                    $now = now();
                    $manager->renew($record, $now, $now->copy()->addMonth());
                    AdminAuditLog::record(
                        Auth::user(),
                        'subscription_marked_paid',
                        'subscription',
                        $record->id,
                        "Manually marked as paid for {$record->company?->name}",
                    );
                    Notification::make()->success()->title(__('admin.mark_paid'))->send();
                    $this->refreshFormData(['status', 'current_period_end', 'last_payment_at']);
                }),
            Action::make('extend_trial')
                ->label(__('admin.extend_trial'))
                ->color('info')
                ->icon('heroicon-o-clock')
                ->visible(fn (Subscription $record) => $record->status === SubscriptionStatus::Trialing)
                ->form([
                    TextInput::make('days')
                        ->label(__('admin.days'))
                        ->numeric()
                        ->default(14)
                        ->required(),
                ])
                ->action(function (array $data, Subscription $record) {
                    $record->update([
                        'trial_ends_at' => now()->addDays((int) $data['days']),
                    ]);
                    AdminAuditLog::record(
                        Auth::user(),
                        'trial_extended',
                        'subscription',
                        $record->id,
                        "Extended trial by {$data['days']} days for {$record->company?->name}",
                    );
                    Notification::make()->success()->title(__('admin.extend_trial'))->send();
                    $this->refreshFormData(['trial_ends_at']);
                }),
        ];
    }
}
