<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
// 關鍵：引用 BusinessResource
use App\Filament\Resources\BusinessResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends BusinessResource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = '客戶管理';
    protected static ?string $modelLabel = '客戶';
    protected static ?string $navigationGroup = '客資管理';
    protected static ?string $navigationIcon = 'heroicon-o-users';

    /**
     * 關鍵修正：透過繼承 BusinessResource 實現租戶隔離
     * 並透過 whereHas 實現 Spatie 角色過濾
     */
    public static function getEloquentQuery(): Builder
    {
        // 這裡 parent 指向 BusinessResource，它會幫你處理 tenant_id
        // 然後我們再額外加上 role 的過濾
        return parent::getEloquentQuery()
            ->whereHas('roles', function (Builder $query) {
                $query->where('name', 'customer');
            });
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->tenant?->slug !== 'central-admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('客戶基本資料')
                    ->description('請填寫客戶的基本登入資訊')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('姓名')
                            ->required()
                            ->placeholder('輸入客戶姓名')
                            ->helperText('此姓名將顯示於系統中'),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('example@domain.com')
                            ->helperText('請輸入有效的電子郵件'),

                        Forms\Components\TextInput::make('password')
                            ->label('密碼')
                            ->password()
                            ->placeholder('設定密碼')
                            ->required(fn(string $context): bool => $context === 'create')
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->dehydrated(fn($state) => filled($state))
                            ->helperText('建立帳號時必填，編輯時可留空'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('姓名')
                    ->searchable()
                    ->sortable()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email 已複製')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('註冊日期')
                    ->dateTime('Y-m-d H:i')
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
                Tables\Actions\EditAction::make()->label('編輯')->color('warning'),
                Tables\Actions\DeleteAction::make()->label('刪除')->color('danger'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('批次刪除'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}