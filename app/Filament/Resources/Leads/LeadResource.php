<?php

namespace App\Filament\Resources\Leads;

use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Models\Lead;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    public static function getNavigationLabel(): string
    {
        return __('admin.leads');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.leads');
    }

    public static function getModelLabel(): string
    {
        return __('admin.lead');
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
                    ->label(__('admin.phone'))
                    ->formatStateUsing(fn ($state) => strlen($state) > 6
                        ? substr($state, 0, 4).'***'.substr($state, -3)
                        : $state
                    )
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('admin.name'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('admin.lead_status'))
                    ->badge(),
                TextColumn::make('source')
                    ->label(__('admin.source'))
                    ->badge(),
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
            'index' => ListLeads::route('/'),
        ];
    }
}
