<?php

namespace App\Filament\Pages;

use App\Models\AdminAuditLog;
use App\Models\PlatformSetting;
use App\Services\AiModelRegistry;
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

class SettingsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.platform_settings');
    }

    public function getTitle(): string
    {
        return __('admin.platform_settings');
    }

    public ?array $data = [];

    public function mount(): void
    {
        $registry = app(AiModelRegistry::class);
        $modelDefaults = [];

        foreach ($registry->all() as $key => $model) {
            $modelDefaults["model_enabled_{$key}"] = PlatformSetting::get("model_enabled_{$key}", true);
        }

        $this->form->fill([
            'default_plan' => PlatformSetting::get('default_plan', 'starter'),
            'max_conversations_per_tenant' => PlatformSetting::get('max_conversations_per_tenant', 1000),
            'max_messages_per_conversation' => PlatformSetting::get('max_messages_per_conversation', 100),
            'max_tokens_per_response' => PlatformSetting::get('max_tokens_per_response', 1024),
            'whatsapp_cost_per_message' => PlatformSetting::get('whatsapp_cost_per_message', 0.005),
            'default_ai_model' => PlatformSetting::get('default_ai_model', $registry->default()),
            'fallback_enabled' => PlatformSetting::get('fallback_enabled', true),
            'fallback_ai_model' => PlatformSetting::get('fallback_ai_model', $registry->fallback()),
            'maintenance_mode' => PlatformSetting::get('maintenance_mode', false),
            'track_analytics' => PlatformSetting::get('track_analytics', true),
            'allow_registration' => PlatformSetting::get('allow_registration', true),
            'default_locale' => PlatformSetting::get('default_locale', 'ar'),
            'billing_grace_days' => PlatformSetting::get('billing_grace_days', 3),
            ...$modelDefaults,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $registry = app(AiModelRegistry::class);
        $models = $registry->all();
        $selectableOptions = [];

        foreach ($models as $key => $model) {
            if (($model['enabled'] ?? true) && ($model['supports_tools'] ?? false)) {
                $selectableOptions[$key] = $model['label'];
            }
        }

        $allModelOptions = [];
        foreach ($models as $key => $model) {
            $allModelOptions[$key] = $model['label'];
        }

        $modelToggles = [];
        foreach ($models as $key => $model) {
            $modelToggles[] = Toggle::make("model_enabled_{$key}")
                ->label($model['label'])
                ->helperText($model['provider'].' · Input: $'.number_format($model['input_cost_per_mtok'], 2).'/M tok · Output: $'.number_format($model['output_cost_per_mtok'], 2).'/M tok')
                ->inline();
        }

        return $schema
            ->components([
                Tabs::make('Settings')
                    ->tabs([
                        Tab::make(__('admin.plan_defaults'))
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                TextInput::make('default_plan')
                                    ->label(__('admin.default_plan'))
                                    ->helperText('e.g. starter, growth, enterprise')
                                    ->required(),
                                TextInput::make('max_conversations_per_tenant')
                                    ->label(__('admin.max_conversations_per_tenant'))
                                    ->numeric()
                                    ->required(),
                                TextInput::make('max_messages_per_conversation')
                                    ->label(__('admin.max_messages_per_conversation'))
                                    ->numeric()
                                    ->required(),
                                TextInput::make('billing_grace_days')
                                    ->label(__('admin.billing_grace_days'))
                                    ->numeric()
                                    ->required(),
                            ]),
                        Tab::make(__('admin.ai_configuration'))
                            ->icon('heroicon-o-cpu-chip')
                            ->schema([
                                ...$modelToggles,
                                Select::make('default_ai_model')
                                    ->label(__('admin.default_ai_model'))
                                    ->options($selectableOptions)
                                    ->helperText('Default model for all companies (selectable models only)')
                                    ->required(),
                                Toggle::make('fallback_enabled')
                                    ->label(__('admin.fallback_enabled'))
                                    ->helperText('Automatically retry with fallback model if primary fails')
                                    ->inline(),
                                Select::make('fallback_ai_model')
                                    ->label(__('admin.fallback_ai_model'))
                                    ->options($allModelOptions)
                                    ->helperText('Model to use when primary fails'),
                                TextInput::make('max_tokens_per_response')
                                    ->label(__('admin.max_tokens_per_response'))
                                    ->numeric()
                                    ->required(),
                                TextInput::make('whatsapp_cost_per_message')
                                    ->label(__('admin.whatsapp_cost_per_message'))
                                    ->numeric()
                                    ->required()
                                    ->step(0.001),
                            ]),
                        Tab::make(__('admin.system'))
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Toggle::make('maintenance_mode')
                                    ->label(__('admin.maintenance_mode'))
                                    ->helperText('When enabled, site shows maintenance page'),
                                Toggle::make('track_analytics')
                                    ->label(__('admin.track_analytics'))
                                    ->helperText('Enable usage analytics collection'),
                                Toggle::make('allow_registration')
                                    ->label(__('admin.allow_registration'))
                                    ->helperText('Allow new company signups'),
                                Select::make('default_locale')
                                    ->label(__('admin.default_locale'))
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
                                ->label(__('admin.save_settings'))
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $before = collect(array_keys($state))
            ->mapWithKeys(fn (string $key) => [$key => PlatformSetting::get($key)])
            ->toArray();

        foreach ($state as $key => $value) {
            PlatformSetting::set($key, $value);
        }

        AdminAuditLog::record(
            auth()->user(),
            'settings_updated',
            'platform_setting',
            null,
            'Platform settings updated',
            $before,
            $state,
        );

        if (isset($state['maintenance_mode'])) {
            if ($state['maintenance_mode']) {
                Artisan::call('down', ['--secret' => 'wakeel-maintenance']);
            } else {
                Artisan::call('up');
            }
        }

        Notification::make()
            ->title(__('admin.settings_saved'))
            ->success()
            ->send();
    }
}
