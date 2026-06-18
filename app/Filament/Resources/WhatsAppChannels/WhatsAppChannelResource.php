<?php

namespace App\Filament\Resources\WhatsAppChannels;

use App\Filament\Resources\WhatsAppChannels\Pages\CreateWhatsAppChannel;
use App\Filament\Resources\WhatsAppChannels\Pages\EditWhatsAppChannel;
use App\Filament\Resources\WhatsAppChannels\Pages\ListWhatsAppChannels;
use App\Filament\Resources\WhatsAppChannels\Schemas\WhatsAppChannelForm;
use App\Filament\Resources\WhatsAppChannels\Tables\WhatsAppChannelsTable;
use App\Models\WhatsAppChannel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class WhatsAppChannelResource extends Resource
{
    protected static ?string $model = WhatsAppChannel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-phone';

    protected static ?string $navigationLabel = 'WhatsApp Channels';

    protected static string|\UnitEnum|null $navigationGroup = 'Platform Management';

    public static function form(Schema $schema): Schema
    {
        return WhatsAppChannelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WhatsAppChannelsTable::configure($table);
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
            'index' => ListWhatsAppChannels::route('/'),
            'create' => CreateWhatsAppChannel::route('/create'),
            'edit' => EditWhatsAppChannel::route('/{record}/edit'),
        ];
    }
}
