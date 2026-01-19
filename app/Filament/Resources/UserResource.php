<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = '使用者管理';
    protected static ?string $modelLabel = '使用者';
    protected static ?string $navigationGroup = '系統管理';
    protected static ?string $navigationIcon = 'heroicon-o-users';

    /**
     * 修正 1：強制顯示選單
     * 只要是超級管理員或資料庫中的 panel_user，就必須看到選單
     */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['super_admin']);
    }

    /**
     * 1. 恢復靜態屬性：這是最穩定的做法，確保普通店長被隔離。
     */
    protected static ?string $tenantOwnershipRelationshipName = 'tenant';

    /**
     * 2. 關鍵修正：透過這個方法，告訴 Filament 如果是 Super Admin，
     * 不要去檢查這筆資料「屬不屬於」當前租戶，這能解決 TypeError。
     */
    public static function isScopedToTenant(): bool
    {
        if (auth()->check() && auth()->user()->hasRole('super_admin')) {
            return false; // 超級管理員不鎖定在租戶範圍
        }

        return true;
    }

    /**
     * 3. 核心查詢：確保清單頁能抓到所有人。
     */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        // 如果是超級管理員，跳過父類別的租戶過濾邏輯，自己寫 Query
        if ($user && $user->hasRole('super_admin')) {
            return static::getModel()::query()->withoutGlobalScopes();
        }

        // 租戶管理員：支援 tenant_admin 或資料庫實際存在的 panel_user
        if ($user && $user->hasAnyRole(['tenant_admin', 'panel_user'])) {
            $tenantId = $user->tenant_id;
            return static::getModel()::query()
                ->where('tenant_id', $tenantId)
                ->withoutGlobalScopes();
        }

        return parent::getEloquentQuery();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本資料')
                    ->description('請填寫使用者的基本資訊')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('姓名')
                            ->required()
                            ->placeholder('輸入姓名')
                            ->prefixIcon('heroicon-o-user')
                            ->helperText('此姓名將顯示於系統中'),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('example@domain.com')
                            ->prefixIcon('heroicon-o-envelope')
                            ->helperText('請輸入有效的電子郵件'),

                        Forms\Components\TextInput::make('password')
                            ->label('密碼')
                            ->password()
                            ->placeholder('設定密碼')
                            ->prefixIcon('heroicon-o-lock-closed')
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context): bool => $context === 'create')
                            ->helperText('建立帳號時必填，編輯時可留空'),

                        Forms\Components\Select::make('roles')
                            ->label('賦予角色')
                            ->relationship('roles', 'name', function (Builder $query) {
                                if (auth()->check() && auth()->user()->hasRole('super_admin')) {
                                    return $query;
                                }
                                return $query->where('tenant_id', Filament::getTenant()?->id);
                            })
                            ->preload()
                            ->multiple()
                            ->searchable()
                            ->hint('可多選，依照租戶權限顯示')
                            ->prefixIcon('heroicon-o-shield-check'),
                    ])
                    ->columns(2),
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
                    ->icon('heroicon-o-user')
                    // 💡 視覺標記：如果是超級管理員，顯示不同的顏色與圖示
                    ->color(fn(User $record) => $record->hasRole('super_admin') ? 'danger' : 'primary'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->icon('heroicon-o-envelope')
                    ->copyable()
                    ->copyMessage('Email 已複製')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('目前角色')
                    ->badge()
                    ->sortable()
                    ->colors([
                        'danger' => 'super_admin', // 特別標註超級管理員
                        'primary' => 'admin',
                        'success' => 'staff',
                        'warning' => 'reception',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('加入時間')
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('角色篩選')
                    ->relationship('roles', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('查看'),
                Tables\Actions\EditAction::make()->label('編輯')->color('warning'),

                // 💡 修改點 1：禁止刪除超級管理員，且禁止刪除「你自己」
                Tables\Actions\DeleteAction::make()
                    ->label('刪除')
                    ->color('danger')
                    ->hidden(
                        fn(User $record) =>
                        $record->hasRole('super_admin') || // 禁止刪除任何超級管理員
                            $record->id === auth()->id()      // 禁止刪除當前登入者
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // 💡 修改點 2：批次刪除時自動過濾掉超級管理員與自己
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('批次刪除')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->filter(
                                fn(User $record) =>
                                !$record->hasRole('super_admin') &&
                                    $record->id !== auth()->id()
                            )->each->delete();
                        }),
                ])->label('更多操作'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}