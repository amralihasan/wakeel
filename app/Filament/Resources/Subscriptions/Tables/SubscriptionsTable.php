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
                    ->label('Company')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Plan')
                    ->badge()
                    ->formatStateUsing(fn ($state) => config('plans.plans.'.$state.'.name', $state)),
                TextColumn::make('stripe_status')
                    ->label('Status')
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
                TextColumn::make('quantity')->label('Qty'),
                TextColumn::make('trial_ends_at')->label('Trial Ends')->dateTime()->sortable(),
                TextColumn::make('ends_at')->label('Ends At')->dateTime()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([]);
    }
}
