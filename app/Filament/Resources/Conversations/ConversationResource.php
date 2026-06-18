<?php

namespace App\Filament\Resources\Conversations;

use App\Filament\Resources\Conversations\Pages\ListConversations;
use App\Models\Conversation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    public static function getNavigationLabel(): string
    {
        return __('admin.conversations');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.conversations');
    }

    public static function getModelLabel(): string
    {
        return __('admin.conversation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.platform_management');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('admin.company'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer_phone')
                    ->label(__('admin.customer_phone'))
                    ->formatStateUsing(fn ($state) => strlen($state) > 6
                        ? substr($state, 0, 4).'***'.substr($state, -3)
                        : $state
                    )
                    ->searchable(),
                TextColumn::make('mode')
                    ->label(__('admin.mode'))
                    ->badge(),
                TextColumn::make('last_message_at')
                    ->label(__('admin.last_activity'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin.time'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConversations::route('/'),
        ];
    }
}
