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
                    ->label('Company')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('lead.customer_phone')
                    ->label('Lead Phone')
                    ->searchable(),
                TextColumn::make('reason')
                    ->searchable(),
                TextColumn::make('ai_summary')
                    ->label('AI Summary')
                    ->limit(50),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ($state?->value ?? $state) {
                        'waiting' => 'warning',
                        'active' => 'info',
                        'resolved' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Escalated At')
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
