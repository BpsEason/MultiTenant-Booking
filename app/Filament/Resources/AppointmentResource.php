<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AppointmentResource extends BusinessResource
{
    protected static ?string $model = Appointment::class;

    // --- 介面中文化設定 ---
    protected static ?string $navigationLabel = '預約管理';
    protected static ?string $modelLabel = '預約';
    protected static ?string $pluralLabel = '預約清單';
    protected static ?string $navigationGroup = '業務管理';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    // 多租戶權限關聯
    protected static ?string $tenantOwnershipRelationshipName = 'tenant';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('預約基本資訊')
                    ->description('請選擇客戶、負責員工及服務項目')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('預約客戶')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('staff_id')
                            ->label('負責員工/教練')
                            ->relationship(
                                name: 'staff',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn(Builder $query) => $query->where('tenant_id', \Filament\Facades\Filament::getTenant()?->id)
                                    ->whereHas('roles', fn(Builder $query) => $query->where('name', 'staff'))
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('service_id')
                            ->label('選擇服務項目')
                            ->relationship('service', 'name')
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn(Set $set, ?string $state, Get $get) => static::updateEndTime($set, $state, $get('start_time')))
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('預約狀態')
                            ->options([
                                'pending' => '等候中',
                                'confirmed' => '已確認',
                                'cancelled' => '已取消',
                                'completed' => '已完成',
                            ])
                            ->default('pending')
                            ->selectablePlaceholder(false)
                            ->native(false)
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('時間安排')
                    ->description('設定預約的開始時間，系統將依據服務時長自動推算結束時間')
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_time')
                            ->label('預約開始時間')
                            ->placeholder('請選擇時間')
                            ->required()
                            ->seconds(false)
                            ->minutesStep(15)
                            ->native(false) // 使用 Filament 美化的選取器
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                static::updateEndTime($set, $get('service_id'), $state);
                            }),

                        Forms\Components\DateTimePicker::make('end_time')
                            ->label('預約結束時間')
                            ->required()
                            ->seconds(false)
                            ->readonly()
                            ->extraAttributes(['class' => 'bg-gray-50']) // 視覺上提示唯讀
                            ->helperText('系統將根據服務項目定義的「服務時長」自動計算'),
                    ])->columns(2),

                Forms\Components\Textarea::make('notes')
                    ->label('備註事項')
                    ->placeholder('紀錄客戶的特殊需求...')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('start_time')
                    ->label('預約時間')
                    ->dateTime('Y/m/d H:i')
                    ->description(fn(Appointment $record): string => '結束於 ' . Carbon::parse($record->end_time)->format('H:i'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('客戶')
                    ->searchable(),

                Tables\Columns\TextColumn::make('staff.name')
                    ->label('負責人')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('服務項目'),

                Tables\Columns\TextColumn::make('status')
                    ->label('狀態')
                    ->badge() // 改用 Badge 顯示更美觀
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => '等候中',
                        'confirmed' => '已確認',
                        'completed' => '已完成',
                        'cancelled' => '已取消',
                        default => $state,
                    }),
            ])
            ->defaultSort('start_time', 'desc') // 預設顯示最新的預約
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('狀態篩選')
                    ->options([
                        'pending' => '等候中',
                        'confirmed' => '已確認',
                        'cancelled' => '已取消',
                        'completed' => '已完成',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('修改'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('整批刪除'),
                ])->label('更多操作'),
            ])
            ->emptyStateHeading('目前尚無預約資料');
    }

    /**
     * 輔助方法：根據服務時長更新結束時間
     */
    protected static function updateEndTime(Set $set, ?string $serviceId, ?string $startTime): void
    {
        if (! $serviceId || ! $startTime) {
            return;
        }

        $service = Service::find($serviceId);
        if ($service && $service->duration_minutes) {
            $set('end_time', Carbon::parse($startTime)->addMinutes($service->duration_minutes)->toDateTimeString());
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}