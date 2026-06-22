<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Conversation;
use Filament\Widgets\ChartWidget;

class GrowthChartWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $pollingInterval = null;

    public function getHeading(): string
    {
        return __('admin.growth_chart');
    }

    protected function getData(): array
    {
        $days = collect(range(29, 0))->map(fn (int $i) => now()->subDays($i)->startOfDay());

        $labels = $days->map(fn ($d) => $d->format('M d'))->toArray();

        $signups = $days->map(fn ($d) => Company::whereDate('created_at', $d)->count())->toArray();

        $conversations = $days->map(
            fn ($d) => Conversation::whereDate('created_at', $d)->count()
        )->toArray();

        return [
            'datasets' => [
                [
                    'label' => __('admin.new_signups'),
                    'data' => $signups,
                    'borderColor' => 'rgb(99, 102, 241)',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => __('admin.conversations_today'),
                    'data' => $conversations,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
