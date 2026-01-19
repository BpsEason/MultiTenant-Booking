<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
// 務必引用 BusinessResource
use App\Filament\Resources\BusinessResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StaffResource extends BusinessResource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = '員工管理';
    protected static ?string $modelLabel = '員工';
    protected static ?string $navigationGroup = '店務管理';
    protected static ?string $navigationIcon = 'heroicon-o-identification';

    /**
     * 關鍵修正：合併「租戶隔離」與「角色過濾」
     */
    public static function getEloquentQuery(): Builder
    {
        // parent::getEloquentQuery() 會自動執行 BusinessResource 裡的租戶過濾 (tenant_id)
        return parent::getEloquentQuery()
            ->whereHas('roles', function (Builder $query) {
                $query->whereIn('name', [
                    'staff',
                    'receptionist', // 櫃檯人員
                    'manager',      // 店長
                ]);
            });
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->tenant?->slug !== 'central-admin';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('員工基本資訊')
                ->description('請填寫員工的基本登入資訊')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('員工姓名')
                        ->required()
                        ->placeholder('輸入姓名')
                        ->prefixIcon('heroicon-o-user')
                        ->helperText('此姓名將顯示於系統中'),

                    Forms\Components\TextInput::make('email')
                        ->label('登入帳號 (Email)')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->placeholder('example@domain.com')
                        ->prefixIcon('heroicon-o-envelope')
                        ->helperText('請輸入有效的電子郵件'),

                    Forms\Components\TextInput::make('password')
                        ->label('初始密碼')
                        ->password()
                        ->placeholder('設定密碼')
                        ->prefixIcon('heroicon-o-lock-closed')
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
                    ->label('加入日期')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('recent')
                    ->label('最近加入')
                    ->query(fn($query) => $query->where('created_at', '>=', now()->subMonth())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('查看'),
                Tables\Actions\EditAction::make()->label('編輯')->color('warning'),
                Tables\Actions\DeleteAction::make()->label('刪除')->color('danger'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('批次刪除'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'edit' => Pages\EditStaff::route('/{record}/edit'),
        ];
    }
}