<?php

namespace App\Filament\Pages;

use App\Models\PlatformSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Artisan;
use UnitEnum;

class SettingsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $title = 'Platform Settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'default_plan' => PlatformSetting::get('default_plan', 'starter'),
            'max_conversations_per_tenant' => PlatformSetting::get('max_conversations_per_tenant', 1000),
            'max_messages_per_conversation' => PlatformSetting::get('max_messages_per_conversation', 100),
            'claude_model' => PlatformSetting::get('claude_model', 'claude-sonnet-4-20250514'),
            'max_tokens_per_response' => PlatformSetting::get('max_tokens_per_response', 1024),
            'whatsapp_cost_per_message' => PlatformSetting::get('whatsapp_cost_per_message', 0.005),
            'ai_cost_per_token_in' => PlatformSetting::get('ai_cost_per_token_in', 0.000003),
            'ai_cost_per_token_out' => PlatformSetting::get('ai_cost_per_token_out', 0.000015),
            'maintenance_mode' => PlatformSetting::get('maintenance_mode', false),
            'track_analytics' => PlatformSetting::get('track_analytics', true),
            'allow_registration' => PlatformSetting::get('allow_registration', true),
            'default_locale' => PlatformSetting::get('default_locale', 'ar'),
            'billing_grace_days' => PlatformSetting::get('billing_grace_days', 3),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Settings')
                    ->tabs([
                        Tab::make('Plan Defaults')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                TextInput::make('default_plan')
                                    ->label('Default Plan Key')
                                    ->helperText('e.g. starter, growth, enterprise')
                                    ->required(),
                                TextInput::make('max_conversations_per_tenant')
                                    ->label('Max Conversations per Tenant')
                                    ->numeric()
                                    ->required(),
                                TextInput::make('max_messages_per_conversation')
                                    ->label('Max Messages per Conversation')
                                    ->numeric()
                                    ->required(),
                                TextInput::make('billing_grace_days')
                                    ->label('Billing Grace Days')
                                    ->numeric()
                                    ->required(),
                            ]),
                        Tab::make('AI Configuration')
                            ->icon('heroicon-o-cpu-chip')
                            ->schema([
                                TextInput::make('claude_model')
                                    ->label('Claude Model Name')
                                    ->required(),
                                TextInput::make('max_tokens_per_response')
                                    ->label('Max Tokens per Response')
                                    ->numeric()
                                    ->required(),
                                TextInput::make('ai_cost_per_token_in')
                                    ->label('AI Cost per Input Token ($)')
                                    ->numeric()
                                    ->required()
                                    ->step(0.000001),
                                TextInput::make('ai_cost_per_token_out')
                                    ->label('AI Cost per Output Token ($)')
                                    ->numeric()
                                    ->required()
                                    ->step(0.000001),
                                TextInput::make('whatsapp_cost_per_message')
                                    ->label('WhatsApp Cost per Message ($)')
                                    ->numeric()
                                    ->required()
                                    ->step(0.001),
                            ]),
                        Tab::make('System')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Toggle::make('maintenance_mode')
                                    ->label('Maintenance Mode')
                                    ->helperText('When enabled, site shows maintenance page'),
                                Toggle::make('track_analytics')
                                    ->label('Track Analytics')
                                    ->helperText('Enable usage analytics collection'),
                                Toggle::make('allow_registration')
                                    ->label('Allow Registration')
                                    ->helperText('Allow new company signups'),
                                Select::make('default_locale')
                                    ->label('Default Locale')
                                    ->options([
                                        'ar' => 'العربية',
                                        'en' => 'English',
                                    ])
                                    ->required(),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Save Settings')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $key => $value) {
            PlatformSetting::set($key, $value);
        }

        if (isset($state['maintenance_mode'])) {
            if ($state['maintenance_mode']) {
                Artisan::call('down', ['--secret' => 'wakeel-maintenance']);
            } else {
                Artisan::call('up');
            }
        }

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}
