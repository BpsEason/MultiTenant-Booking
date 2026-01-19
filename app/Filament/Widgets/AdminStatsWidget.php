<?php

namespace App\Filament\Widgets;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Appointment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('總合作租戶', Tenant::count())
                ->description('目前全站診所、健身房總數')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),

            Stat::make('全系統帳號數', User::count())
                ->description('包含所有租戶的員工與客戶')
                ->descriptionIcon('heroicon-m-users'),

            Stat::make('累計預約總量', Appointment::count())
                ->description('全站歷史總預約筆數')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),
        ];
    }

    // 關鍵：只有中央管理中心的帳號才看得到這個 Widget
    public static function canView(): bool
    {
        return auth()->user()->tenant?->slug === 'central-admin';
    }
}