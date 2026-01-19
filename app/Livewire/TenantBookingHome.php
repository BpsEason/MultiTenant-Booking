<?php

namespace App\Livewire;

use App\Models\Tenant;
use Livewire\Component;
use Livewire\Attributes\Computed; // 引入計算屬性標籤

class TenantBookingHome extends Component
{
    // 透過 Route Model Binding 傳入的 Tenant 實例
    public Tenant $tenant;

    /**
     * 掛載組件
     */
    public function mount(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    /**
     * 使用 Computed Property (計算屬性) 抓取服務
     * 優點：
     * 1. 只有在 Blade 呼叫 $this->services 時才會執行查詢
     * 2. 在同一個請求週期內會快取結果，避免重複查詢資料庫
     */
    #[Computed]
    public function services()
    {
        // 建議實作：
        // 1. 使用關聯 $this->tenant->services() 確保資料一致性
        // 2. 增加 is_active 判斷，避免顯示已停用的服務
        return $this->tenant->services()
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc') // 建議增加排序，讓店長可調整順序
            ->get();
    }

    public function render()
    {
        return view('livewire.tenant-booking-home', [
            // 顯式傳入變數，這樣 Blade 就能直接讀到 $services
            'services' => $this->services,
        ])->layout('layouts.guest');
    }
}