<?php

namespace App\Filament\Resources\WhatsAppChannels\Pages;

use App\Filament\Resources\WhatsAppChannels\WhatsAppChannelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppChannels extends ListRecords
{
    protected static string $resource = WhatsAppChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
