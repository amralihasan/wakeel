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
                Section::make(__('admin.whatsapp_channel'))
                    ->schema([
                        TextInput::make('number')
                            ->label(__('admin.number'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder('+20xxxxxxxxxx'),
                        TextInput::make('channel_id')
                            ->label(__('admin.channel_id'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Select::make('status')
                            ->label(__('admin.status'))
                            ->options([
                                'available' => __('admin.available'),
                                'assigned' => __('admin.assigned'),
                                'retired' => __('admin.retired'),
                            ])
                            ->default('available')
                            ->required(),
                        Select::make('assigned_company_id')
                            ->label(__('admin.assigned_company'))
                            ->relationship('company', 'name')
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }
}
