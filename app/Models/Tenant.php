<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Exception; // 引入異常類別

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'settings',
    ];

    protected $casts = [
        'settings' => 'json',
    ];

    /**
     * 系統核心邏輯保護
     */
    protected static function booted()
    {
        // 監聽刪除事件
        static::deleting(function ($tenant) {
            // ID 1 為 SaaS 系統管理中心，禁止刪除
            if ($tenant->id === 1 || $tenant->slug === 'central-admin') {
                // 丟出例外會直接中斷刪除流程並回滾交易 (Transaction)
                throw new Exception("警告：此為系統核心管理租戶，禁止刪除。");
            }
        });
    }

    // --- 關聯設定 ---

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    // --- 工具方法 ---

    public function getSetting(string $key, $default = null)
    {
        $defaults = [
            'primary_color' => '#4F46E5', // 建議對齊你 Blade 用的變數名
            'booking_interval' => 30,
            'cancellation_limit_hours' => 24,
        ];

        return data_get($this->settings, $key, $defaults[$key] ?? $default);
    }
}