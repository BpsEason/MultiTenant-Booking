<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

abstract class BusinessResource extends Resource
{
    // 關鍵：這讓所有繼承它的 Resource 都自動具備租戶隔離功能
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', auth()->user()->tenant_id);
    }

    public static function shouldRegisterNavigation(): bool
    {
        // 確保只有非中央管理員可以看到這些業務選單
        return auth()->user()->tenant?->slug !== 'central-admin';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->tenant?->slug !== 'central-admin';
    }
}