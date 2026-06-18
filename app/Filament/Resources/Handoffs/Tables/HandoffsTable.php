<?php

namespace App\Filament\Resources\Handoffs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HandoffsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('admin.company'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('lead.customer_phone')
                    ->label(__('admin.lead_phone'))
                    ->searchable(),
                TextColumn::make('reason')
                    ->label(__('admin.reason'))
                    ->searchable(),
                TextColumn::make('ai_summary')
                    ->label(__('admin.ai_summary'))
                    ->limit(50),
                TextColumn::make('status')
                    ->label(__('admin.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state?->value ?? $state) {
                        'waiting' => __('admin.pending'),
                        'active' => __('admin.in_progress'),
                        'resolved' => __('admin.resolved'),
                        default => $state,
                    })
                    ->color(fn ($state): string => match ($state?->value ?? $state) {
                        'waiting' => 'warning',
                        'active' => 'info',
                        'resolved' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label(__('admin.escalated_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
