<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Service extends Model
{
    use HasFactory;

    /**
     * 批量賦值屬性
     * 這些欄位必須與您的 Migration 檔案 一致
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'image_path',
        'is_active',
        'sort_order',
        'is_popular',
        'duration_minutes',
        'price',
    ];

    /**
     * 資料格式轉型
     */
    protected $casts = [
        'price' => 'decimal:2',
        'duration_minutes' => 'integer',
    ];

    /**
     * 關聯：所屬租戶
     * 確保此服務歸屬於特定的診所、健身房或美容院
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * 關聯：此服務下的所有預約紀錄
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * 輔助方法：格式化顯示價格與時長
     * 方便在 Filament 後台或前端顯示
     */
    public function getNameWithDurationAttribute(): string
    {
        return "{$this->name} ({$this->duration_minutes} 分鐘)";
    }
}