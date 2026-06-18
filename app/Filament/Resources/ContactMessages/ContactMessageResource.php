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
        return 'Contact Messages';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Contact Messages';
    }

    public static function getModelLabel(): string
    {
        return 'Contact Message';
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
                Section::make('Message')
                    ->schema([
                        Text::make('name'),
                        Text::make('email'),
                        Text::make('phone'),
                        Text::make('company'),
                        Text::make('message'),
                        Text::make('locale'),
                        Text::make('created_at')->dateTime(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('is_handled')
                    ->label('Handled')
                    ->badge()
                    ->state(fn ($record) => $record->is_handled ? 'Yes' : 'No')
                    ->color(fn ($record) => $record->is_handled ? 'success' : 'warning'),
                TextColumn::make('locale')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->recordActions([
                Action::make('mark_handled')
                    ->label('Mark Handled')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ContactMessage $record) => ! $record->is_handled)
                    ->action(fn (ContactMessage $record) => $record->update([
                        'is_handled' => true,
                        'handled_by' => Auth::id(),
                        'handled_at' => now(),
                    ])),
                Action::make('mark_unhandled')
                    ->label('Reopen')
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
