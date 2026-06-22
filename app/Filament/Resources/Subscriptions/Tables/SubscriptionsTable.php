<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('admin.company'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('plan_key')
                    ->label(__('admin.plan'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('admin.'.$state)),
                TextColumn::make('status')
                    ->label(__('admin.status'))
                    ->badge()
                    ->color(fn (SubscriptionStatus $state): string => match ($state) {
                        SubscriptionStatus::Active => 'success',
                        SubscriptionStatus::Trialing => 'info',
                        SubscriptionStatus::PastDue => 'warning',
                        SubscriptionStatus::Canceled => 'danger',
                        SubscriptionStatus::Expired => 'gray',
                    })
                    ->formatStateUsing(fn (SubscriptionStatus $state) => $state->label()),
                TextColumn::make('payment_method')
                    ->label(__('admin.payment_method'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('current_period_end')
                    ->label(__('admin.next_charge'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('mrr')
                    ->label(__('admin.est_revenue'))
                    ->money('USD')
                    ->state(fn (Subscription $record) => $record->plan()['price_cents'] / 100 / 100),
                TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.status'))
                    ->options(fn () => collect(SubscriptionStatus::cases())
                        ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                        ->toArray()),
                SelectFilter::make('payment_method')
                    ->label(__('admin.payment_method'))
                    ->options([
                        'card' => __('billing.card'),
                        'wallet' => __('billing.wallet'),
                        'valu' => __('billing.valu'),
                    ]),
                SelectFilter::make('plan_key')
                    ->label(__('admin.plan'))
                    ->options([
                        'starter' => __('admin.starter'),
                        'growth' => __('admin.growth'),
                        'enterprise' => __('admin.enterprise'),
                    ]),
            ]);
    }
}
