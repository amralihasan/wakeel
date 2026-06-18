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
                Section::make(__('admin.handoff'))
                    ->schema([
                        Select::make('status')
                            ->label(__('admin.status'))
                            ->options([
                                'waiting' => __('admin.pending'),
                                'active' => __('admin.in_progress'),
                                'resolved' => __('admin.resolved'),
                            ])
                            ->required(),
                        Textarea::make('resolution_notes')
                            ->label(__('admin.resolution_notes'))
                            ->rows(3)
                            ->nullable(),
                    ])
                    ->columns(1),
            ]);
    }
}
