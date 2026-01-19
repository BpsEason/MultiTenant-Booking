<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    /**
     * 存檔後自動指派 'customer' 角色
     */
    protected function afterCreate(): void
    {
        $this->record->assignRole('customer');
    }

    /**
     * 儲存後回到列表清單
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}