<?php

namespace App\Filament\Resources\WhatsAppChannels\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WhatsAppChannelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('WhatsApp Channel details')
                    ->schema([
                        TextInput::make('number')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('+20xxxxxxxxxx'),
                        TextInput::make('channel_id')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Select::make('status')
                            ->options([
                                'available' => 'Available',
                                'assigned' => 'Assigned',
                                'retired' => 'Retired',
                            ])
                            ->default('available')
                            ->required(),
                        Select::make('assigned_company_id')
                            ->label('Assigned Company')
                            ->relationship('company', 'name')
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }
}
