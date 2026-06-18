<?php

namespace App\Filament\Resources\Handoffs;

use App\Filament\Resources\Handoffs\Pages\CreateHandoff;
use App\Filament\Resources\Handoffs\Pages\EditHandoff;
use App\Filament\Resources\Handoffs\Pages\ListHandoffs;
use App\Filament\Resources\Handoffs\Schemas\HandoffForm;
use App\Filament\Resources\Handoffs\Tables\HandoffsTable;
use App\Models\Handoff;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class HandoffResource extends Resource
{
    protected static ?string $model = Handoff::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Escalations / Handoffs';

    protected static string|\UnitEnum|null $navigationGroup = 'Platform Management';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return HandoffForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HandoffsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHandoffs::route('/'),
            'create' => CreateHandoff::route('/create'),
            'edit' => EditHandoff::route('/{record}/edit'),
        ];
    }
}
