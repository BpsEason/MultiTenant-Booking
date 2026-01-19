<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;

class TenantSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = '系統設定';
    protected static ?string $navigationGroup = '系統管理';
    protected static string $view = 'filament.pages.tenant-settings';

    /**
     * 表單數據容器
     */
    public ?array $data = [];

    public function mount(): void
    {
        // 1. 安全攔截
        if (auth()->user()->hasRole('super_admin')) {
            abort(403, '超級管理員請至中央管理區。');
        }

        // 2. 核心修正：直接將 settings 陣列塞進 fill
        // 因為 statePath('data') 會自動將 fill 的內容對接到 $this->data
        $this->form->fill(Filament::getTenant()->settings ?? []);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('品牌化設定 (Branding)')
                    ->description('讓店家擁有自己的品牌風格')
                    ->schema([
                        ColorPicker::make('primary_color')
                            ->label('主色系')
                            ->default('#3b82f6'),
                        TextInput::make('welcome_msg')
                            ->label('登入頁歡迎語'),
                    ])->columns(2),

                Section::make('營運參數設定')
                    ->description('控制預約邏輯與排程規則')
                    ->schema([
                        Select::make('booking_interval')
                            ->label('預約時間間距')
                            ->options([
                                15 => '每 15 分鐘一格',
                                30 => '每 30 分鐘一格',
                                60 => '每 60 分鐘一格',
                            ])->required(),
                        TextInput::make('cancellation_limit_hours')
                            ->label('最晚取消時間 (小時)')
                            ->numeric()
                            ->suffix('小時')
                            ->default(24),
                    ])->columns(2),
            ])
            ->statePath('data'); // 這是關鍵：表單所有欄位都在 $this->data 底下
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        // 隱藏選單：不讓超級管理員看到
        if ($user?->hasRole('super_admin')) {
            return false;
        }

        return $user?->hasAnyRole(['tenant_admin', 'panel_user']);
    }

    public function save(): void
    {
        // 取得驗證後的資料（這會直接拿 $this->data 的內容）
        $settings = $this->form->getState();

        // 存回資料庫
        Filament::getTenant()->update([
            'settings' => $settings
        ]);

        Notification::make()
            ->title('設定儲存成功')
            ->success()
            ->send();
    }
}