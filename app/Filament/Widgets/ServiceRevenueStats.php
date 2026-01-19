<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Cache;
use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Facades\Filament;

class ServiceRevenueStats extends BaseWidget
{
    use InteractsWithPageFilters;

    // 整個 widget 橫跨全寬
    protected int | string | array $columnSpan = 'full';

    // 強制一排 3 個卡片
    protected static ?int $columns = 3;

    protected function getStats(): array
    {
        $tenant = Filament::getTenant();
        $tenantId = $tenant?->id;

        if (!$tenantId) {
            return [
                Stat::make('錯誤', '無法取得租戶資訊')
                    ->color('danger')
                    ->description('請確認已登入租戶面板'),
            ];
        }

        $start = $this->filters['start']
            ? Carbon::parse($this->filters['start'])->startOfDay()
            : now()->startOfMonth();

        $end = $this->filters['end']
            ? Carbon::parse($this->filters['end'])->endOfDay()
            : now()->endOfDay();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        // 計算與上一期比較的區間長度
        $daysCount = $start->diffInDays($end);

        $cacheKey = "tenant_{$tenantId}_service_revenue_summary_{$start->format('Ymd')}_{$end->format('Ymd')}";

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($tenantId, $start, $end, $daysCount) {
            $query = Appointment::query()
                ->join('services', 'appointments.service_id', '=', 'services.id')
                ->where('appointments.tenant_id', $tenantId)
                ->whereBetween('appointments.start_time', [$start, $end])
                ->where('appointments.status', 'completed');

            $totalRevenue = $query->clone()->sum('services.price');

            // 上一期同長度時間比較
            $prevStart = $start->copy()->subDays($daysCount + 1);
            $prevEnd   = $start->copy()->subSecond();
            $prevRevenue = Appointment::query()
                ->join('services', 'appointments.service_id', '=', 'services.id')
                ->where('appointments.tenant_id', $tenantId)
                ->whereBetween('appointments.start_time', [$prevStart, $prevEnd])
                ->where('appointments.status', 'completed')
                ->sum('services.price');

            $trendPercent = $prevRevenue > 0 ? (($totalRevenue - $prevRevenue) / $prevRevenue) * 100 : 0;

            // Top 2 服務
            $topServices = $query->clone()
                ->selectRaw('appointments.service_id, SUM(services.price) as total, COUNT(*) as count')
                ->groupBy('appointments.service_id')
                ->orderByDesc('total')
                ->limit(2)
                ->with('service:id,name')
                ->get();

            return [
                'total_revenue'  => $totalRevenue,
                'trend_percent'  => $trendPercent,
                'top_services'   => $topServices,
            ];
        });

        $stats = [];

        // 第一張：總營收 + 成長率
        $trend = $data['trend_percent'];
        $stats[] = Stat::make('本期總營收', 'NT$ ' . number_format($data['total_revenue']))
            ->description($trend >= 0
                ? "較上期成長 " . number_format($trend, 1) . "%"
                : "較上期衰退 " . number_format(abs($trend), 1) . "%")
            ->descriptionIcon($trend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->color($trend >= 0 ? 'success' : 'danger');

        // 第二張：最高營收服務
        if (isset($data['top_services'][0])) {
            $top1 = $data['top_services'][0];
            $name1 = $top1->service?->name ?? '未知服務';
            $stats[] = Stat::make($name1, 'NT$ ' . number_format($top1->total))
                ->description("{$top1->count} 筆訂單 · Top 1")
                ->descriptionIcon('heroicon-m-star')
                ->color('primary');
        } else {
            $stats[] = Stat::make('無 Top 服務', 'NT$ 0')
                ->description('本期無完成預約')
                ->color('gray');
        }

        // 第三張：第二高營收服務
        if (isset($data['top_services'][1])) {
            $top2 = $data['top_services'][1];
            $name2 = $top2->service?->name ?? '未知服務';
            $stats[] = Stat::make($name2, 'NT$ ' . number_format($top2->total))
                ->description("{$top2->count} 筆訂單 · Top 2")
                ->descriptionIcon('heroicon-m-arrow-up')
                ->color('info');
        } else {
            $stats[] = Stat::make('其他服務', 'NT$ 0')
                ->description('本期僅有一項服務或無數據')
                ->color('gray');
        }

        return $stats;
    }
}