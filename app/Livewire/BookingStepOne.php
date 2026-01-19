<?php

namespace App\Livewire;

use App\Models\Tenant;
use App\Models\Service;
use Livewire\Component;

class BookingStepOne extends Component
{
    public Tenant $tenant;
    public Service $service;

    /**
     * 掛載組件
     * 這裡的參數名稱 ($tenant, $service) 必須與 routes/web.php 中的 {tenant} 與 {service} 完全一致
     */
    public function mount(Tenant $tenant, $service)
    {
        $this->tenant = $tenant;

        // 如果 $service 不是物件（而是 ID），手動抓取
        if (!($service instanceof \App\Models\Service)) {
            $this->service = \App\Models\Service::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->findOrFail($service);
        } else {
            $this->service = $service;
        }
    }

    public function render()
    {
        // 顯式指定 Layout 為 resources/views/layouts/guest.blade.php
        return view('livewire.booking-step-one')
            ->layout('layouts.guest');
    }
}