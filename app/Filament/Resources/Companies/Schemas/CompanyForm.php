<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Services\AiModelRegistry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        $registry = app(AiModelRegistry::class);
        $modelOptions = [];

        foreach ($registry->selectable() as $key => $model) {
            $modelOptions[$key] = $model['label'];
        }

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
                        Select::make('ai_model')
                            ->label(__('admin.ai_model'))
                            ->options([
                                null => __('admin.use_platform_default'),
                                ...$modelOptions,
                            ])
                            ->helperText(__('admin.ai_model_help')),
                        Toggle::make('is_active')
                            ->label(__('admin.active'))
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
