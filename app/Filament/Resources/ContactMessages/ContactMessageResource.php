<?php

namespace App\Filament\Resources\ContactMessages;

use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox';

    public static function getNavigationLabel(): string
    {
        return __('admin.contact_messages');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.contact_messages');
    }

    public static function getModelLabel(): string
    {
        return __('admin.contact_message');
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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.message'))
                    ->schema([
                        Text::make('name')->label(__('admin.name')),
                        Text::make('email')->label(__('admin.email')),
                        Text::make('phone')->label(__('admin.phone')),
                        Text::make('company')->label(__('admin.company')),
                        Text::make('message')->label(__('admin.message')),
                        Text::make('locale')->label(__('admin.locale')),
                        Text::make('created_at')->label(__('admin.time'))->dateTime(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('admin.name'))->searchable()->sortable(),
                TextColumn::make('email')->label(__('admin.email'))->searchable(),
                TextColumn::make('is_handled')
                    ->label(__('admin.status'))
                    ->badge()
                    ->state(fn ($record) => $record->is_handled ? __('admin.yes') : __('admin.no'))
                    ->color(fn ($record) => $record->is_handled ? 'success' : 'warning'),
                TextColumn::make('locale')->label(__('admin.locale'))->badge(),
                TextColumn::make('created_at')->label(__('admin.time'))->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->recordActions([
                Action::make('mark_handled')
                    ->label(__('admin.mark_handled'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ContactMessage $record) => ! $record->is_handled)
                    ->action(fn (ContactMessage $record) => $record->update([
                        'is_handled' => true,
                        'handled_by' => Auth::id(),
                        'handled_at' => now(),
                    ])),
                Action::make('mark_unhandled')
                    ->label(__('admin.reopen'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (ContactMessage $record) => $record->is_handled)
                    ->action(fn (ContactMessage $record) => $record->update([
                        'is_handled' => false,
                        'handled_by' => null,
                        'handled_at' => null,
                    ])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactMessages::route('/'),
        ];
    }
}
