<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Message;
use Filament\Widgets\ChartWidget;

class RevenueVsCostChartWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $pollingInterval = null;

    public function getHeading(): string
    {
        return __('admin.revenue_vs_cost');
    }

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn (int $i) => now()->subMonths($i));

        $labels = $months->map(fn ($m) => $m->format('M Y'))->toArray();

        $revenueData = $months->map(function ($month) {
            return Company::where('is_active', true)
                ->whereDate('created_at', '<=', $month->endOfMonth())
                ->get()
                ->sum(fn (Company $c) => ($c->getPlanDetails()['price_cents'] ?? 0) / 100);
        })->toArray();

        $costData = $months->map(function ($month) {
            $tokens = Message::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->selectRaw('SUM(input_tokens) as total_input, SUM(output_tokens) as total_output, COUNT(DISTINCT conversation_id) as conv_count')
                ->first();

            $inputCost = (($tokens?->total_input ?? 0) / 1000) * 0.00025;
            $outputCost = (($tokens?->total_output ?? 0) / 1000) * 0.00125;
            $whatsappCost = ($tokens?->conv_count ?? 0) * 0.03;

            return round($inputCost + $outputCost + $whatsappCost, 2);
        })->toArray();

        return [
            'datasets' => [
                [
                    'label' => __('admin.mrr'),
                    'data' => $revenueData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.6)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => __('admin.est_cost'),
                    'data' => $costData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.6)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
