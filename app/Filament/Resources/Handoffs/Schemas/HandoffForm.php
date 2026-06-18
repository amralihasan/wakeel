<?php

namespace App\Filament\Resources\Handoffs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HandoffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Handoff details')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'waiting' => 'Waiting',
                                'active' => 'Active',
                                'resolved' => 'Resolved',
                            ])
                            ->required(),
                        Textarea::make('resolution_notes')
                            ->rows(3)
                            ->nullable(),
                    ])
                    ->columns(1),
            ]);
    }
}
