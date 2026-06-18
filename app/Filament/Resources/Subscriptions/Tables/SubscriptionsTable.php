<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionsTable
{
    public static function create(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('admin.company'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('admin.plan'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('admin.'.$state)),
                TextColumn::make('stripe_status')
                    ->label(__('admin.stripe_status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trialing' => 'info',
                        'past_due' => 'warning',
                        'canceled' => 'danger',
                        'incomplete' => 'warning',
                        'incomplete_expired' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('quantity')->label(__('admin.quantity')),
                TextColumn::make('trial_ends_at')->label(__('admin.trial_ends'))->dateTime()->sortable(),
                TextColumn::make('ends_at')->label(__('admin.ends_at'))->dateTime()->sortable(),
                TextColumn::make('created_at')->label(__('admin.time'))->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([]);
    }
}
