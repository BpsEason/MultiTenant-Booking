<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServiceResource extends BusinessResource
{
    protected static ?string $model = Service::class;

    // --- 繁體中文化介面設定 ---
    protected static ?string $navigationLabel = '服務項目';
    protected static ?string $modelLabel = '服務項目';
    protected static ?string $pluralLabel = '服務項目清單';
    protected static ?string $navigationGroup = '業務管理';
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    // 關鍵：開啟多租戶擁有權，Filament 會自動根據當前租戶過濾服務項目
    protected static ?string $tenantOwnershipRelationshipName = 'tenant';


    // 1. 控制側邊欄是否顯示
    public static function shouldRegisterNavigation(): bool
    {
        // 取得當前登入使用者的租戶 slug
        $tenantSlug = auth()->user()->tenant?->slug;

        // 如果租戶不是 central-admin，才顯示導覽項目
        return $tenantSlug !== 'central-admin';
    }

    // 2. 加強安全：如果上帝嘗試透過網址直接進入，也給予拒絕
    public static function canViewAny(): bool
    {
        return auth()->user()->tenant?->slug !== 'central-admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // 第一區塊：基本資訊（並排，寬鬆）
                Forms\Components\Section::make('基本資訊')
                    ->description('必填項目，客戶最先看到的內容')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('服務名稱')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('duration_minutes')
                                ->label('服務時長')
                                ->numeric()
                                ->required()
                                ->suffix('分鐘')
                                ->minValue(1),

                            Forms\Components\TextInput::make('price')
                                ->label('收費價格')
                                ->numeric()
                                ->required()
                                ->prefix('NT$')
                                ->minValue(0),
                        ]),
                    ]),

                // 第二區塊：說明與圖片（跨全寬）
                Forms\Components\Section::make('詳細內容')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('服務說明')
                            ->rows(5)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('image_path')
                            ->label('服務圖片')
                            ->image()
                            ->disk('public')
                            ->directory('services')
                            ->preserveFilenames()
                            ->imageEditor()
                            ->imageCropAspectRatio('4:3')
                            ->columnSpanFull(),
                    ]),

                // 第三區塊：推薦設定（單獨一行）
                Forms\Components\Section::make('推薦設定')
                    ->schema([
                        Forms\Components\Toggle::make('is_popular')
                            ->label('設為熱門推薦')
                            ->inline(false),
                    ]),
            ])
            ->columns(1); // 整體單欄，讓表單垂直流暢
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 服務名稱 - 加大字重 + 限制長度避免過長換行
                Tables\Columns\TextColumn::make('name')
                    ->label('服務項目名稱')
                    ->searchable()
                    ->sortable()
                    ->limit(40) // 避免名稱太長擠爆
                    ->tooltip(fn($state) => $state) // 滑鼠移上去顯示完整名稱
                    ->weight('bold')
                    ->wrap(), // 允許換行

                // 服務時長 - 加大 badge 視覺感
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('服務時長')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn(string $state): string => "{$state} 分鐘")
                    ->extraAttributes(['class' => 'text-base font-medium']),

                // 價格 - 加大字體 + 粗體，右對齊
                Tables\Columns\TextColumn::make('price')
                    ->label('服務價格')
                    ->money('TWD')
                    ->sortable()
                    ->alignEnd()
                    ->color('success')
                    ->weight('bold')
                    ->extraAttributes(['class' => 'text-lg']),

                // 是否熱門 - 加大圖示 + 文字說明
                Tables\Columns\IconColumn::make('is_popular')
                    ->label('熱門推薦')
                    ->boolean()
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->icon(fn(bool $state): string => $state ? 'heroicon-s-star' : 'heroicon-o-star')
                    ->sortable()
                    ->tooltip(fn(bool $state): string => $state ? '熱門推薦項目' : '一般項目'),

                // 建立日期 - 保持，但加格式化
                Tables\Columns\TextColumn::make('created_at')
                    ->label('建立日期')
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // 可加一個簡單的熱門篩選器（選用）
                Tables\Filters\TernaryFilter::make('is_popular')
                    ->label('熱門推薦')
                    ->trueLabel('僅顯示熱門')
                    ->falseLabel('僅顯示一般')
                    ->placeholder('全部'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('修改')
                    ->icon('heroicon-o-pencil-square'),
                Tables\Actions\DeleteAction::make()
                    ->label('刪除')
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('整批刪除'),
                ])->label('更多操作'),
            ])
            ->emptyStateHeading('目前尚無服務項目')
            ->emptyStateDescription('點擊右上角「新增服務項目」來建立您的營業清單。')
            ->emptyStateIcon('heroicon-o-sparkles')
            ->striped() // 加條紋背景，讓列表更易閱讀
            ->poll('30s'); // 每 30 秒自動刷新（可選，適合即時更新）
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}