<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Actions\Action;
use Filament\Forms; // 💡 關鍵：引用 Forms 基礎類別
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\AppointmentResource;
use App\Filament\Resources\TenantResource;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $title = '管理主控台';

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    public function getSubheading(): ?string
    {
        $user   = Auth::user();
        $tenant = $user?->tenant;

        if ($tenant?->slug === 'central-admin') {
            return '全站監控・租戶健康度・系統營收總覽';
        }

        return $tenant
            ? "掌握 {$tenant->name} 今日預約動態與營運表現"
            : '營運控制中心';
    }

    /**
     * 💡 排版優化：使用 Section 包裹讓介面具備層次感
     */
    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('數據篩選')
                    ->description('調整下方數據圖表的統計週期')
                    ->compact() // 緊湊模式，節省空間
                    ->collapsible() // 允許收納
                    ->icon('heroicon-m-funnel') // 加入漏斗圖示
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\DatePicker::make('start')
                                    ->label('開始日期')
                                    ->native(false)
                                    ->prefixIcon('heroicon-m-calendar-days')
                                    ->displayFormat('Y-m-d')
                                    ->default(now()->startOfMonth())
                                    ->maxDate(fn(Forms\Get $get) => $get('end') ?? now())
                                    ->live(),

                                Forms\Components\DatePicker::make('end')
                                    ->label('結束日期')
                                    ->native(false)
                                    ->prefixIcon('heroicon-m-calendar')
                                    ->displayFormat('Y-m-d')
                                    ->default(now())
                                    ->minDate(fn(Forms\Get $get) => $get('start'))
                                    ->maxDate(now())
                                    ->live(),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('filters');
    }

    /**
     * 快速動作按鈕
     */
    protected function getHeaderActions(): array
    {
        $user   = Auth::user();
        $tenant = $user?->tenant;
        $isCentralAdmin = $tenant?->slug === 'central-admin';

        $actions = [];

        if ($isCentralAdmin) {
            $actions[] = Action::make('manage_tenants')
                ->label('租戶管理')
                ->icon('heroicon-o-building-office')
                ->color('info')
                ->url(fn() => TenantResource::getUrl('index'));
        } else {
            $actions[] = Action::make('create_appointment')
                ->label('新增預約')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->url(fn() => AppointmentResource::getUrl('create'));

            // 注意：這裡假設你有對應的路由
            if ($user?->hasRole('店長')) {
                $actions[] = Action::make('revenue_report')
                    ->label('營收報表')
                    ->icon('heroicon-o-chart-pie')
                    ->color('warning')
                    ->url(fn() => '#'); // 替換為實際路由
            }
        }

        return $actions;
    }

    public function getColumns(): int|string|array
    {
        return [
            'default' => 1,
            'sm'      => 2,
            'lg'      => 3,
            '2xl'     => 4,
        ];
    }

    public function getWidgets(): array
    {
        $user   = Auth::user();
        $tenant = $user?->tenant;

        // 💡 邏輯更嚴謹：如果是 Super Admin 且在中央管理租戶下
        if ($tenant?->slug === 'central-admin' && $user?->hasRole('super_admin')) {
            return [
                \App\Filament\Widgets\AdminStatsOverview::class,
                \App\Filament\Widgets\GlobalRevenueTrendChart::class,
            ];
        }

        return [
            \App\Filament\Widgets\TodayAppointmentsStats::class,
            \App\Filament\Widgets\ServiceRevenueStats::class,
            \App\Filament\Widgets\AppointmentCalendarWidget::class,
        ];
    }

    public function getTitle(): string
    {
        $tenant = Auth::user()?->tenant;

        return $tenant?->slug === 'central-admin'
            ? 'SaaS 平台總管理中心'
            : ($tenant ? "{$tenant->name} 營運控制台" : '營運後台');
    }
}