<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class TodayAppointmentsStats extends BaseWidget
{
    // 強制每行 4 個卡片（這是關鍵設定）
    protected static ?int $columns = 4;

    // 建議同時設定 columnSpan = 'full'，讓整個區塊橫跨全寬
    protected int | string | array $columnSpan = 'full';

    // 可選：每 30 秒自動重新整理數據
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $tenant = Filament::getTenant();
        $tenantId = $tenant?->id;

        if (!$tenantId) {
            return [
                Stat::make('錯誤', '無法取得租戶資訊')
                    ->color('danger')
                    ->description('請確認已登入租戶帳號'),
            ];
        }

        $today = Carbon::today();

        $cacheKey = "tenant_{$tenantId}_today_stats_{$today->format('Ymd')}";

        $statsData = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($tenantId, $today) {
            // 今日總預約數（所有狀態）
            $totalToday = Appointment::where('tenant_id', $tenantId)
                ->whereDate('start_time', $today)
                ->count();

            // 已取消數
            $canceled = Appointment::where('tenant_id', $tenantId)
                ->whereDate('start_time', $today)
                ->where('status', 'cancelled') // ← 請確認你的取消狀態值
                ->count();

            // 取消率（避免除以 0）
            $cancelRate = $totalToday > 0 ? round(($canceled / $totalToday) * 100, 1) : 0;

            // 活躍員工數（今日有預約的獨立員工）
            $activeStaff = Appointment::where('tenant_id', $tenantId)
                ->whereDate('start_time', $today)
                ->distinct('staff_id')
                ->count('staff_id');

            return [
                'total_today'   => $totalToday,
                'canceled'      => $canceled,
                'cancel_rate'   => $cancelRate,
                'active_staff'  => $activeStaff,
            ];
        });

        $totalToday  = $statsData['total_today'];
        $cancelRate  = $statsData['cancel_rate'];
        $activeStaff = $statsData['active_staff'];

        return [
            Stat::make('今日預約總數', $totalToday)
                ->description('本日所有預約（含未完成）')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('取消率', $cancelRate . '%')
                ->description($statsData['canceled'] . ' 筆已取消')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($cancelRate > 15 ? 'danger' : ($cancelRate > 5 ? 'warning' : 'success')),

            Stat::make('活躍員工', $activeStaff)
                ->description('今日有排班/預約的員工')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            // 第四個卡片（可根據需求替換內容）
            Stat::make('今日新客數', '0') // ← 這裡可以改成實際計算
                ->description('首次預約客戶')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info'),
        ];
    }
}