<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Facades\Cache;
use App\Models\Appointment;
use Carbon\Carbon;

class GlobalRevenueTrendChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = '全站每月營收趨勢';

    // 建議讓趨勢圖跨全寬，看起來更專業
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $start = $this->filters['start']
            ? Carbon::parse($this->filters['start'])->startOfMonth()
            : now()->subMonths(12)->startOfMonth();

        $end = $this->filters['end']
            ? Carbon::parse($this->filters['end'])->endOfMonth()
            : now()->endOfMonth();

        $cacheKey = "global_revenue_trend_{$start->format('Ymd')}_{$end->format('Ymd')}";

        $trend = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($start, $end) {
            return Trend::query(
                Appointment::query()
                    ->where('status', 'completed') // ← 這裡先用 Eloquent 過濾
            )
                ->between(start: $start, end: $end)
                ->perMonth()
                ->sum('price'); // ← 如果 appointments 有 price 欄位；否則見下方註解
        });

        return [
            'datasets' => [
                [
                    'label'           => '每月營收 (NTD)',
                    'data'            => $trend->map(fn(TrendValue $value) => (float) $value->aggregate)->toArray(),
                    'borderColor'     => '#6366f1',
                    'backgroundColor' => '#6366f140',
                    'fill'            => true,
                    'tension'         => 0.3,
                ],
            ],
            'labels' => $trend->map(fn(TrendValue $value) => Carbon::parse($value->date)->format('Y-m'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}