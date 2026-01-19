<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    // --- 名稱修改區域 ---
    protected static ?string $navigationLabel = '租戶管理'; // 側邊欄顯示名稱
    protected static ?string $pluralLabel = '租戶清單';     // 列表頁標題
    protected static ?string $modelLabel = '租戶';         // 按鈕顯示名稱 (如：新增租戶)
    // ------------------

    protected static bool $isScopedToTenant = false;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = '系統管理';

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public static function canDelete($record): bool
    {
        if (!$record) return auth()->user()->hasRole('super_admin');
        return $record->id !== 1 && auth()->user()->hasRole('super_admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('🏢 租戶基本資訊')
                    ->description('診所、健身房或其他租戶的核心識別資訊')
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('租戶名稱')
                            ->placeholder('例如：Beyond 健身中心')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Forms\Set $set, ?string $state) => $set('slug', Str::slug($state)))
                            ->helperText('此名稱將顯示於前台與後台'),

                        Forms\Components\TextInput::make('slug')
                            ->label('網址路徑 (Slug)')
                            ->placeholder('例如：beyond-gym')
                            ->required()
                            ->maxLength(255)
                            ->unique(Tenant::class, 'slug', ignoreRecord: true)
                            ->prefix(config('app.url') . '/admin/')
                            ->helperText('此路徑將作為租戶專屬的管理網址'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('🎨 白標化品牌設定')
                    ->description('設定租戶專屬的品牌視覺（儲存於 JSON settings 欄位）')
                    ->collapsible()
                    ->schema([
                        Forms\Components\ColorPicker::make('settings.primary_color')
                            ->label('主題顏色')
                            ->default('#fbbf24')
                            ->helperText('此顏色將用於前台與後台的主要按鈕與標題'),

                        Forms\Components\FileUpload::make('settings.logo_url')
                            ->label('品牌 Logo')
                            ->image()
                            ->directory('tenant-logos')
                            ->helperText('建議使用透明背景 PNG，尺寸 512x512'),

                        Forms\Components\Textarea::make('settings.welcome_text')
                            ->label('後台歡迎標語')
                            ->placeholder('歡迎回來！今天也要提供最優質的服務。')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('此文字將顯示於租戶後台首頁'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('租戶名稱')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    // 💡 視覺優化：如果是 ID 1，加上一個盾牌圖示標註為系統核心
                    ->icon(fn(Tenant $record) => $record->id === 1 ? 'heroicon-m-shield-check' : null)
                    ->tooltip(fn(Tenant $record) => $record->id === 1 ? '系統管理中心 (受保護)' : '點擊可查看詳細資訊'),

                Tables\Columns\TextColumn::make('slug')
                    ->label('網址路徑')
                    ->copyable()
                    ->copyMessage('已複製租戶網址')
                    ->copyMessageDuration(1500)
                    ->badge()
                    ->color('gray'),

                Tables\Columns\ColorColumn::make('settings.primary_color')
                    ->label('主題色'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('註冊時間')
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('recent')
                    ->label('最近註冊')
                    ->query(fn($query) => $query->where('created_at', '>=', now()->subMonth())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('查看'),
                Tables\Actions\EditAction::make()->label('修改')->color('warning'),

                // 💡 修改點 1：針對 ID 1 隱藏「刪除」按鈕
                Tables\Actions\DeleteAction::make()
                    ->label('刪除')
                    ->color('danger')
                    ->hidden(fn(Tenant $record) => $record->id === 1),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // 💡 修改點 2：改寫整批刪除邏輯，強制過濾掉 ID 1
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('整批刪除')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->filter(fn($record) => $record->id !== 1)->each->delete();
                        }),
                ])->label('更多操作'),
            ])
            ->emptyStateHeading('目前尚無租戶資料')
            ->emptyStateDescription('建立第一個租戶以開始使用系統');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}