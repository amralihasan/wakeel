<?php

namespace App\Filament\Resources\AdminAuditLogs;

use App\Filament\Resources\AdminAuditLogs\Pages\ListAdminAuditLogs;
use App\Models\AdminAuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AdminAuditLogResource extends Resource
{
    protected static ?string $model = AdminAuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    public static function getNavigationLabel(): string
    {
        return __('admin.audit_log');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.audit_log');
    }

    public static function getModelLabel(): string
    {
        return __('admin.audit_log');
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
                TextColumn::make('actor.name')
                    ->label(__('admin.actor'))
                    ->searchable(),
                TextColumn::make('action')
                    ->label(__('admin.action'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('target_type')
                    ->label(__('admin.target'))
                    ->searchable(),
                TextColumn::make('description')
                    ->label(__('admin.description'))
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('ip')
                    ->label(__('admin.ip'))
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminAuditLogs::route('/'),
        ];
    }
}
