<?php

namespace App\Filament\Resources\WhatsAppChannels\Pages;

use App\Filament\Resources\WhatsAppChannels\WhatsAppChannelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWhatsAppChannel extends EditRecord
{
    protected static string $resource = WhatsAppChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
