<?php

namespace App\Filament\Resources\Handoffs\Pages;

use App\Filament\Resources\Handoffs\HandoffResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHandoff extends EditRecord
{
    protected static string $resource = HandoffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
