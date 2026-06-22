<?php

namespace App\Filament\Resources\GroundingViolations;

use App\Filament\Resources\GroundingViolations\Pages\ListGroundingViolations;
use App\Models\GroundingViolation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class GroundingViolationResource extends Resource
{
    protected static ?string $model = GroundingViolation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    public static function getNavigationLabel(): string
    {
        return __('admin.grounding_violations');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.grounding_violations');
    }

    public static function getModelLabel(): string
    {
        return __('admin.grounding_violation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.system_monitoring');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('admin.time'))
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
                TextColumn::make('company.name')
                    ->label(__('admin.company'))
                    ->searchable(),
                TextColumn::make('model_used')
                    ->label(__('admin.model_used'))
                    ->searchable(),
                TextColumn::make('action_taken')
                    ->label(__('admin.action_taken'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'blocked' => 'danger',
                        'regenerated' => 'warning',
                        'fallback_sent' => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => __("admin.{$state}"))
                    ->searchable(),
                TextColumn::make('violation_reason')
                    ->label(__('admin.violation_reason'))
                    ->limit(80)
                    ->searchable(),
                TextColumn::make('original_text')
                    ->label(__('admin.original_text'))
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGroundingViolations::route('/'),
        ];
    }
}
