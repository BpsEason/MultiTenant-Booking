<?php

namespace App\Filament\Resources\StaffResource\Pages;

use App\Filament\Resources\StaffResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    // 在資料存入 users 表後，自動在中間表建立角色關聯
    protected function afterCreate(): void
    {
        // 這是 Spatie 提供的 method
        $this->record->assignRole('staff');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}