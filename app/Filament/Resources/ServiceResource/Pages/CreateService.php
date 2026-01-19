<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    // 關鍵：覆寫跳轉路徑
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}