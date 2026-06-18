<?php

namespace App\Filament\Resources\Handoffs\Pages;

use App\Filament\Resources\Handoffs\HandoffResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHandoffs extends ListRecords
{
    protected static string $resource = HandoffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
