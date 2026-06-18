<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConversationsRelationManager extends RelationManager
{
    protected static string $relationship = 'conversations';

    protected static ?string $recordTitleAttribute = 'customer_phone';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_phone')
                    ->label('Customer')
                    ->formatStateUsing(fn ($state) => strlen($state) > 6
                        ? substr($state, 0, 4).'***'.substr($state, -3)
                        : $state
                    ),
                TextColumn::make('mode')->badge(),
                TextColumn::make('last_message_at')->label('Last Activity')->dateTime()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([]);
    }
}
