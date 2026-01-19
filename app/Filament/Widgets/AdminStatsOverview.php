<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Cache;
use App\Models\Tenant;
use App\Models\Appointment;
use Carbon\Carbon;

class AdminStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $start = $this->filters['start']
            ? Carbon::parse($this->filters['start'])
            : now()->subDays(30)->startOfDay();

        $end = $this->filters['end']
            ? Carbon::parse($this->filters['end'])->endOfDay()
            : now()->endOfDay();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $cacheKey = "admin_global_stats_{$start->format('Ymd')}_{$end->format('Ymd')}";

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($start, $end) {
            return [
                'active_tenants' => Tenant::whereHas('users')->count(),
                'total_bookings' => Appointment::whereBetween('start_time', [$start, $end])->count(),
                'total_revenue'  => Appointment::whereBetween('start_time', [$start, $end])
                    ->where('status', 'completed')
                    ->sum('price'),  // 若無 price 欄位，請改用 join services
            ];
        });

        return [
            Stat::make('活躍租戶數', number_format($data['active_tenants']))
                ->description('擁有使用者的租戶')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),

            Stat::make('區間預約總數', number_format($data['total_bookings']))
                ->description("{$start->format('m/d')} 至 {$end->format('m/d')}")
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),

            Stat::make('區間總營收', 'NT$ ' . number_format($data['total_revenue']))
                ->description('已完成預約')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),
        ];
    }
}