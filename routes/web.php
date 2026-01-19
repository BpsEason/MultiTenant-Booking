<?php

use App\Livewire\TenantBookingHome;
use App\Livewire\BookingCalendar; // 這是我們之前修好的那個日曆組件
use Illuminate\Support\Facades\Route;

// --- 1. 系統全域入口 ---
Route::get('/', function () {
    return view('welcome');
});

// --- 2. 租戶功能組 ---
Route::prefix('/{tenant:slug}')->group(function () {
    // 租戶首頁
    Route::get('/', TenantBookingHome::class)->name('tenant.frontend');

    // 修正點：指向 BookingStepOne，並將參數改為 {service} 以支援 Route Model Binding
    Route::get('/book/{service}', \App\Livewire\BookingStepOne::class)
        ->name('tenant.booking');

    // 成功頁面
    Route::get('/success', function () {
        return view('tenant.booking-success');
    })->name('tenant.booking.success');
});