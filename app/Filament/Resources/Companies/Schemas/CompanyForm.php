<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.company'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.company_name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('admin.email'))
                            ->email()
                            ->required(),
                        Select::make('plan')
                            ->label(__('admin.plan'))
                            ->options([
                                'starter' => __('admin.starter'),
                                'growth' => __('admin.growth'),
                                'enterprise' => __('admin.enterprise'),
                            ])
                            ->required(),
                        Toggle::make('is_active')
                            ->label(__('admin.active'))
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
