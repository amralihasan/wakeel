<?php

namespace App\Filament\Resources\WhatsAppChannels\Tables;

use App\Models\WhatsAppChannel;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WhatsAppChannelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('channel_id')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'gray',
                        'assigned' => 'success',
                        'retired' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('company.name')
                    ->label('Assigned Company'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('retire')
                    ->label('Retire')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-o-archive-box')
                    ->visible(fn (WhatsAppChannel $record): bool => $record->status !== 'retired')
                    ->action(fn (WhatsAppChannel $record) => $record->update(['status' => 'retired'])),
                Action::make('make_available')
                    ->label('Make Available')
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (WhatsAppChannel $record): bool => $record->status !== 'available')
                    ->action(fn (WhatsAppChannel $record) => $record->update([
                        'status' => 'available',
                        'assigned_company_id' => null,
                    ])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
