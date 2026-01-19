<?php

namespace App\Providers\Filament;

use App\Models\Tenant;
use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Facades\Filament;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Filament\Navigation\MenuItem;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Blade;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()

            // --- 視覺與操作增強 ---
            ->maxContentWidth('full')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->sidebarCollapsibleOnDesktop()

            // 1. 【多租戶核心】
            ->tenant(Tenant::class, slugAttribute: 'slug')
            ->tenantMenu(false)

            // 2. 【品牌與顏色】
            // 修正：這裡必須給予靜態的 Color 類別或字串，不可傳入 Closure 避免 TypeError
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->brandName(fn() => Filament::getTenant()?->name ?? 'SaaS 預約管理中心')

            // 3. 【帳號下拉選單】
            ->tenantMenuItems([
                MenuItem::make()
                    ->label('返回中央管理')
                    ->url('/admin/central-admin')
                    ->icon('heroicon-o-shield-check')
                    ->visible(fn() => auth()->user()?->hasRole('super_admin', 'web')),

                MenuItem::make()
                    ->label('租戶設定')
                    ->url(fn() => \App\Filament\Pages\TenantSettings::getUrl())
                    ->icon('heroicon-o-cog-6-tooth')
                    ->visible(fn() => auth()->user()?->hasAnyRole(['super_admin', 'tenant_admin', 'panel_user'], 'web')),
            ])

            // 4. 【插件配置】
            ->plugins([
                FilamentShieldPlugin::make()
                    ->gridColumns(['default' => 1, 'sm' => 2, 'lg' => 3])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns(['default' => 1, 'sm' => 2, 'lg' => 2])
                    ->resourceCheckboxListColumns(['default' => 1, 'sm' => 2, 'lg' => 2]),

                FilamentFullCalendarPlugin::make()
                    ->selectable()
                    ->editable()
            ])

            // 5. 【頁面與路徑配置】
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                \Filament\Http\Middleware\AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                \Filament\Http\Middleware\Authenticate::class,
            ]);
    }

    /**
     * 修正重點：使用 boot 方法並註冊 RenderHook
     * 這能在頁面渲染時，動態地將租戶自訂的顏色注入到 CSS 變數中
     */
    public function boot(): void
    {
        FilamentView::registerRenderHook(
            'panels::styles.after',
            fn(): string => Blade::render('
                @if ($tenant = \Filament\Facades\Filament::getTenant())
                    @php
                        $primaryColor = $tenant->settings["brand_color"] ?? null;
                    @endphp

                    @if ($primaryColor)
                        <style>
                            :root {
                                /* 覆寫 Filament 的 Primary 顏色變數 */
                                --primary-50: {{ $primaryColor }};
                                --primary-100: {{ $primaryColor }};
                                --primary-200: {{ $primaryColor }};
                                --primary-300: {{ $primaryColor }};
                                --primary-400: {{ $primaryColor }};
                                --primary-500: {{ $primaryColor }};
                                --primary-600: {{ $primaryColor }};
                                --primary-700: {{ $primaryColor }};
                                --primary-800: {{ $primaryColor }};
                                --primary-900: {{ $primaryColor }};
                                --primary-950: {{ $primaryColor }};
                            }
                        </style>
                    @endif
                @endif
            '),
        );
    }
}